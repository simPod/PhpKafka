<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Consumer;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RdKafka\KafkaConsumer as RdKafkaConsumer;
use RdKafka\Message;
use RdKafka\TopicPartition;
use SimPod\Kafka\Clients\Consumer\Exception\IncompatibleStatus;
use SimPod\Kafka\Common\Exception\Wakeup;
use function array_map;
use function pcntl_signal_dispatch;
use function rd_kafka_err2str;
use function sprintf;
use const RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS;
use const RD_KAFKA_RESP_ERR__PARTITION_EOF;
use const RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS;
use const RD_KAFKA_RESP_ERR__TIMED_OUT;
use const RD_KAFKA_RESP_ERR_NO_ERROR;

final class KafkaConsumer extends RdKafkaConsumer
{
    use WithSignalControl;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(ConsumerConfig $config, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();

        $this->setupInternalTerminationSignal($config);

        $config->getConf()->setErrorCb(
            function (RdKafkaConsumer $kafka, int $err, string $reason) : void {
                $this->logger->error(
                    sprintf('Kafka error: "%s": "%s"', rd_kafka_err2str($err), $reason),
                    ['err' => $err]
                );
            }
        );

        $rebalanceCb =
            /** @param array<string, TopicPartition>|null $partitions */
            function (RdKafkaConsumer $kafka, int $err, ?array $partitions = null) : void {
                switch ($err) {
                    case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                        $this->logger->info(
                            'Assigning partitions',
                            $partitions === null ? [] : array_map(
                                static function (TopicPartition $partition) : string {
                                    return (string) $partition->getPartition();
                                },
                                $partitions
                            )
                        );
                        $kafka->assign($partitions);
                        break;
                    case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                        $this->logger->info(
                            'Revoking partitions',
                            $partitions === null ? [] : array_map(
                                static function (TopicPartition $partition) : string {
                                    return (string) $partition->getPartition();
                                },
                                $partitions
                            )
                        );
                        $kafka->assign();
                        break;
                    default:
                        $this->logger->error(sprintf('Rebalancing failed: %s (%d)', rd_kafka_err2str($err), $err));
                        $kafka->assign();
                }
            };
        $config->getConf()->setRebalanceCb($rebalanceCb);

        parent::__construct($config->getConf());
    }

    /**
     * @param callable(Message) : void $onSuccess
     * @param callable() : void        $onPartitionEof
     * @param callable() : void        $onTimedOut
     */
    public function start(
        int $timeoutMs,
        callable $onSuccess,
        ?callable $onPartitionEof = null,
        ?callable $onTimedOut = null
    ) : void {
        $this->doStart($timeoutMs, $onSuccess, $onPartitionEof, $onTimedOut);
    }

    /**
     * @param callable(Message) : void         $processRecord
     * @param callable(ConsumerRecords) : void $onBatchProcessed
     */
    public function startBatch(
        int $maxBatchSize,
        int $timeoutMs,
        ?callable $processRecord = null,
        ?callable $onBatchProcessed = null
    ) : void {
        $batchTime       = new BatchTime($timeoutMs);
        $consumerRecords = new ConsumerRecords();

        $this->doStart(
            $timeoutMs,
            function (Message $message) use (
                $maxBatchSize,
                $timeoutMs,
                $batchTime,
                $processRecord,
                $onBatchProcessed,
                $consumerRecords
            ) : void {
                $consumerRecords->add($message);
                if ($processRecord !== null) {
                    $processRecord($message);
                }

                if ($consumerRecords->count() === $maxBatchSize) {
                    if ($onBatchProcessed !== null && ! $consumerRecords->isEmpty()) {
                        $onBatchProcessed($consumerRecords);
                    }

                    $consumerRecords->clear();
                    $batchTime->reset($timeoutMs);

                    return;
                }

                $this->checkBatchTimedOut($timeoutMs, $batchTime, $onBatchProcessed, $consumerRecords)();
            },
            $this->checkBatchTimedOut($timeoutMs, $batchTime, $onBatchProcessed, $consumerRecords),
            $this->checkBatchTimedOut($timeoutMs, $batchTime, $onBatchProcessed, $consumerRecords)
        );
    }

    /**
     * @param callable(Message) : void $onSuccess
     * @param callable() : void        $onPartitionEof
     * @param callable() : void        $onTimedOut
     */
    private function doStart(
        int $timeoutMs,
        callable $onSuccess,
        ?callable $onPartitionEof = null,
        ?callable $onTimedOut = null
    ) : void {
        $this->registerSignals();

        try {
            while (true) {
                pcntl_signal_dispatch();

                $message = $this->consume($timeoutMs);

                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        $onSuccess($message);

                        break;
                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        if ($onPartitionEof !== null) {
                            $onPartitionEof();
                        }

                        $this->logger->info('No more messages. Will wait for more');

                        break;
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        $this->logger->info(sprintf('Timed out with timeout %d ms', $timeoutMs));
                        if ($onTimedOut !== null) {
                            $onTimedOut();
                        }

                        break;
                    default:
                        $exception = IncompatibleStatus::fromMessage($message);
                        $this->logger->error($exception->getMessage(), ['exception' => $exception]);
                }
            }
        } catch (Wakeup $wakeup) {
        }

        $this->degisterSignals();
    }

    /**
     * @param callable(ConsumerRecords) : void|null $onBatchProcessed
     *
     * @return callable() : void
     */
    private function checkBatchTimedOut(
        int $timeoutMs,
        BatchTime $batchTime,
        ?callable $onBatchProcessed,
        ConsumerRecords $consumerRecords
    ) : callable {
        return static function () use (
            $timeoutMs,
            $batchTime,
            $onBatchProcessed,
            $consumerRecords
        ) : void {
            $remainingTimeout = $batchTime->endTime - (new DateTimeImmutable())->getTimestamp() * 1000;

            if ($remainingTimeout >= 0) {
                return;
            }

            if ($onBatchProcessed !== null && ! $consumerRecords->isEmpty()) {
                $onBatchProcessed($consumerRecords);
            }

            $consumerRecords->clear();
            $batchTime->reset($timeoutMs);
        };
    }

    public function shutdown() : void
    {
        $this->logger->info('Shutting down');

        throw new Wakeup();
    }
}
