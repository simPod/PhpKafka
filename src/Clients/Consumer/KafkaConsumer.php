<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Consumer;

use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RdKafka\KafkaConsumer as RdKafkaConsumer;
use RdKafka\Message;
use SimPod\Kafka\Clients\Consumer\Exception\IncompatibleStatus;
use SimPod\Kafka\Common\Exception\Wakeup;
use function pcntl_signal_dispatch;
use function rd_kafka_err2str;
use function sprintf;
use const RD_KAFKA_RESP_ERR__PARTITION_EOF;
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
            function ($kafka, $err, $reason) : void {
                $this->logger->error(
                    sprintf('Kafka error: "%s": "%s"', rd_kafka_err2str($err), $reason),
                    ['err' => $err]
                );
            }
        );

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
        ?callable $processRecord = null,
        ?callable $onBatchProcessed = null,
        ?int $maxBatchSize = null,
        ?int $timeoutMs = null
    ) : void {
        if ($maxBatchSize === null && ($timeoutMs === null || $timeoutMs <= 0)) {
            throw new InvalidArgumentException('You have to specify either batch size or/and positive timeout');
        }

        if ($timeoutMs === null) {
            $remainingTimeout = $timeoutMs = 1000;
            $end              = null;
        } else {
            $remainingTimeout = $timeoutMs;
            $end              = $this->getEnd($timeoutMs);
        }
        $consumerRecords = new ConsumerRecords();

        $this->doStart(
            $remainingTimeout,
            function (Message $message) use (
                $maxBatchSize,
                $timeoutMs,
                &$remainingTimeout,
                &$end,
                $processRecord,
                $onBatchProcessed,
                &$consumerRecords
            ) : void {
                $consumerRecords->add($message);
                if ($processRecord !== null) {
                    $processRecord($message);
                }

                if ($consumerRecords->count() === $maxBatchSize) {
                    if ($onBatchProcessed !== null && ! $consumerRecords->isEmpty()) {
                        $onBatchProcessed($consumerRecords);
                    }

                    $consumerRecords  = new ConsumerRecords();
                    $remainingTimeout = $timeoutMs;
                    if ($end !== null) {
                        $end = $this->getEnd($end);
                    }

                    return;
                }

                $this->checkBatchTimedOut($timeoutMs, $remainingTimeout, $end, $onBatchProcessed, $consumerRecords)();
            },
            $this->checkBatchTimedOut($timeoutMs, $remainingTimeout, $end, $onBatchProcessed, $consumerRecords),
            $this->checkBatchTimedOut($timeoutMs, $remainingTimeout, $end, $onBatchProcessed, $consumerRecords)
        );
    }

    /**
     * @param callable(Message) : void $onSuccess
     * @param callable() : void        $onPartitionEof
     * @param callable() : void        $onTimedOut
     */
    private function doStart(
        int &$timeoutMs,
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
        int &$remainingTimeout,
        ?int &$end,
        ?callable $onBatchProcessed,
        ConsumerRecords &$consumerRecords
    ) : callable {
        return function () use (
            $timeoutMs,
            &$remainingTimeout,
            &$end,
            $onBatchProcessed,
            &$consumerRecords
        ) : void {
            if ($end === null) {
                return;
            }

            $remainingTimeout = $end - (new DateTimeImmutable())->getTimestamp() * 1000;

            if ($remainingTimeout >= 0) {
                return;
            }

            if ($onBatchProcessed !== null && ! $consumerRecords->isEmpty()) {
                $onBatchProcessed($consumerRecords);
            }

            $consumerRecords  = new ConsumerRecords();
            $remainingTimeout = $timeoutMs;
            $end              = $this->getEnd($timeoutMs);
        };
    }

    public function shutdown() : void
    {
        $this->logger->info('Shutting down');

        throw new Wakeup();
    }

    private function getEnd(int $timeoutMs) : int
    {
        return (new DateTimeImmutable())->getTimestamp() * 1000 + $timeoutMs;
    }
}
