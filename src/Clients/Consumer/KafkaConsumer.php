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

use function array_map;
use function rd_kafka_err2str;
use function Safe\pcntl_signal_dispatch;
use function sprintf;

use const RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS;
use const RD_KAFKA_RESP_ERR__PARTITION_EOF;
use const RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS;
use const RD_KAFKA_RESP_ERR__TIMED_OUT;
use const RD_KAFKA_RESP_ERR_NO_ERROR;

final class KafkaConsumer extends RdKafkaConsumer
{
    use WithSignalControl;

    private LoggerInterface $logger;

    private bool $shouldRun = true;

    public function __construct(ConsumerConfig $config, LoggerInterface|null $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();

        $this->setupInternalTerminationSignal($config);

        $config->getConf()->setErrorCb(
            function (RdKafkaConsumer $kafka, int $err, string $reason): void {
                $this->logger->error(
                    sprintf('Kafka error: "%s": "%s"', rd_kafka_err2str($err), $reason),
                    ['err' => $err],
                );
            },
        );

        $rebalanceCallback =
            function (RdKafkaConsumer $kafka, int $err, array|null $partitions = null): void {
                /** @phpstan-var array<string, TopicPartition>|null $partitions */
                switch ($err) {
                    case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                        $this->logger->debug(
                            'Assigning partitions',
                            $partitions === null ? [] : array_map(
                                static fn (TopicPartition $partition): string => (string) $partition->getPartition(),
                                $partitions,
                            ),
                        );
                        $kafka->assign($partitions);

                        break;
                    case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                        $this->logger->debug(
                            'Revoking partitions',
                            $partitions === null ? [] : array_map(
                                static fn (TopicPartition $partition): string => (string) $partition->getPartition(),
                                $partitions,
                            ),
                        );
                        $kafka->assign();

                        break;
                    default:
                        $this->logger->error(sprintf('Rebalancing failed: %s (%d)', rd_kafka_err2str($err), $err));
                        $kafka->assign();
                }
            };
        $config->getConf()->setRebalanceCb($rebalanceCallback);

        parent::__construct($config->getConf());
    }

    /**
     * @param callable(Message):void $onSuccess
     * @param (callable():void)|null $onPartitionEof
     * @param (callable():void)|null $onTimedOut
     */
    public function start(
        int $timeoutMs,
        callable $onSuccess,
        callable|null $onPartitionEof = null,
        callable|null $onTimedOut = null,
    ): void {
        $this->doStart($timeoutMs, $onSuccess, $onPartitionEof, $onTimedOut);
    }

    /**
     * @param (callable(Message):void)|null         $processRecord
     * @param (callable(ConsumerRecords):void)|null $onBatchProcessed
     */
    public function startBatch(
        int $maxBatchSize,
        int $timeoutMs,
        callable|null $processRecord = null,
        callable|null $onBatchProcessed = null,
    ): void {
        $batchTime = new BatchTime($timeoutMs, new DateTimeImmutable());
        $consumerRecords = new ConsumerRecords();

        $this->doStart(
            $timeoutMs,
            function (Message $message) use (
                $maxBatchSize,
                $timeoutMs,
                $batchTime,
                $processRecord,
                $onBatchProcessed,
                $consumerRecords,
            ): void {
                $consumerRecords->add($message);
                if ($processRecord !== null) {
                    $processRecord($message);
                }

                if ($consumerRecords->count() === $maxBatchSize) {
                    if ($onBatchProcessed !== null && ! $consumerRecords->isEmpty()) {
                        $onBatchProcessed($consumerRecords);
                    }

                    $consumerRecords->clear();
                    $batchTime->reset($timeoutMs, new DateTimeImmutable());

                    return;
                }

                $this->checkBatchTimedOut($timeoutMs, $batchTime, $onBatchProcessed, $consumerRecords)();
            },
            $this->checkBatchTimedOut($timeoutMs, $batchTime, $onBatchProcessed, $consumerRecords),
            $this->checkBatchTimedOut($timeoutMs, $batchTime, $onBatchProcessed, $consumerRecords),
        );
    }

    public function shutdown(): void
    {
        $this->logger->debug('Shutting down');

        $this->stop();
    }

    public function stop(): void
    {
        $this->shouldRun = false;
    }

    /**
     * @param callable(Message):void $onSuccess
     * @param (callable():void)|null       $onPartitionEof
     * @param (callable():void)|null       $onTimedOut
     */
    private function doStart(
        int $timeoutMs,
        callable $onSuccess,
        callable|null $onPartitionEof = null,
        callable|null $onTimedOut = null,
    ): void {
        $this->shouldRun = true;
        $terminationCallback = fn () => $this->stop();
        $this->registerSignals($terminationCallback);

        while ($this->shouldRun) {
            $message = $this->consume($timeoutMs);

            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $onSuccess($message);

                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    if ($onPartitionEof !== null) {
                        $onPartitionEof();
                    }

                    $this->logger->debug('No more messages. Will wait for more');

                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    $this->logger->debug(sprintf('Timed out with timeout %d ms', $timeoutMs));
                    if ($onTimedOut !== null) {
                        $onTimedOut();
                    }

                    break;
                default:
                    $exception = IncompatibleStatus::fromMessage($message);
                    $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            }

            pcntl_signal_dispatch();
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
        callable|null $onBatchProcessed,
        ConsumerRecords $consumerRecords,
    ): callable {
        return static function () use (
            $timeoutMs,
            $batchTime,
            $onBatchProcessed,
            $consumerRecords,
        ): void {
            $remainingTimeout = $batchTime->endMsTimestamp - (new DateTimeImmutable())->getTimestamp() * 1000;

            if ($remainingTimeout >= 0) {
                return;
            }

            if ($onBatchProcessed !== null && ! $consumerRecords->isEmpty()) {
                $onBatchProcessed($consumerRecords);
            }

            $consumerRecords->clear();
            $batchTime->reset($timeoutMs, new DateTimeImmutable());
        };
    }
}
