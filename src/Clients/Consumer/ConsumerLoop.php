<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Consumer;

use Closure;
use LogicException;
use Psr\Log\LoggerInterface;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use SimPod\Kafka\Clients\Consumer\Exception\IncompatibleStatus;
use Throwable;

use function count;
use function hrtime;
use function intdiv;
use function min;
use function strlen;

use const RD_KAFKA_RESP_ERR__ALL_BROKERS_DOWN;
use const RD_KAFKA_RESP_ERR__PARTITION_EOF;
use const RD_KAFKA_RESP_ERR__TIMED_OUT;
use const RD_KAFKA_RESP_ERR__TRANSPORT;
use const RD_KAFKA_RESP_ERR_NO_ERROR;

/** @internal Shared polling and processing for the runner and the native compatibility adapter. */
final class ConsumerLoop
{
    private bool $running = false;

    private bool $finished = false;

    private bool $stopRequested = false;

    /** @var list<Message> */
    private array $pending = [];

    private int $bytes = 0;

    private int|null $startedAt = null;

    /** @var Closure(ConsumerBatch):void|null */
    private Closure|null $handler = null;

    private ConsumerBatch|null $activeBatch = null;

    private bool $commitAfterProcessing = false;

    private bool $commitRequested = false;

    public function __construct(private readonly KafkaConsumer $consumer, private readonly LoggerInterface $logger)
    {
    }

    /**
     * @param callable(ConsumerBatch):void $handler
     * @param (callable():void)|null $onPartitionEof
     * @param (callable():void)|null $onTimedOut
     */
    public function run(
        BatchLimits $limits,
        callable $handler,
        bool $commitAfterProcessing,
        callable|null $onPartitionEof = null,
        callable|null $onTimedOut = null,
    ): void {
        if ($this->running || $this->finished) {
            throw new LogicException('A consumer run is already active or has finished; create a new consumer');
        }

        $this->running = true;
        $this->handler = Closure::fromCallable($handler);
        $this->commitAfterProcessing = $commitAfterProcessing;

        try {
            while (! $this->stopRequested) {
                $waitMs = $limits->pollWaitMs;
                if ($this->startedAt !== null) {
                    $remaining = $limits->maxAgeMs * 1_000_000 - ((int) hrtime(true) - $this->startedAt);
                    if ($remaining <= 0) {
                        $this->flush();

                        continue;
                    }

                    $waitMs = min($waitMs, intdiv($remaining, 1_000_000));
                }

                $message = $this->consumer->consume($waitMs);
                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        $recordBytes = strlen($message->payload ?? '') + strlen($message->key ?? '');
                        if (
                            $limits->maxBytes !== null
                            && $this->pending !== []
                            && $recordBytes > $limits->maxBytes - $this->bytes
                        ) {
                            $this->flush();
                        }

                        $this->startedAt ??= (int) hrtime(true);
                        $this->pending[] = $message;
                        $this->bytes += $recordBytes;
                        if (
                            count($this->pending) >= $limits->maxRecords
                            || ($limits->maxBytes !== null && $this->bytes >= $limits->maxBytes)
                        ) {
                            $this->flush();
                        }

                        break;
                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        if ($onPartitionEof !== null) {
                            $onPartitionEof();
                        }

                        break;
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        if ($onTimedOut !== null) {
                            $onTimedOut();
                        }

                        break;
                    case RD_KAFKA_RESP_ERR__TRANSPORT:
                    case RD_KAFKA_RESP_ERR__ALL_BROKERS_DOWN:
                        $this->logger->warning(
                            'Kafka transport unavailable; native consumer will reconnect',
                            ['err' => $message->err],
                        );

                        break;
                    default:
                        throw IncompatibleStatus::fromMessage($message);
                }
            }

            // Only a normal stop drains. Exceptions leave all failed work unacknowledged.
            $this->flush();
        } catch (Throwable $exception) {
            $this->stopRequested = true;

            throw $exception;
        } finally {
            $this->discardPending();
            $this->handler = null;
            $this->running = false;
            $this->finished = true;
        }
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function commitBatch(ConsumerBatch $batch): void
    {
        if ($this->activeBatch !== $batch) {
            throw new LogicException('Only the batch currently being processed can be committed');
        }

        $this->commitRequested = true;
    }

    /** Drain before releasing ownership; called inside the native rebalance callback. */
    public function flush(): void
    {
        if ($this->pending === []) {
            return;
        }

        $handler = $this->handler;
        if ($handler === null || $this->activeBatch !== null) {
            throw new LogicException('Cannot drain a batch outside its run or during processing');
        }

        $batch = new ConsumerBatch($this->pending);
        $this->discardPending();
        $this->activeBatch = $batch;
        $this->commitRequested = false;
        try {
            $handler($batch);
            if ($this->commitAfterProcessing || $this->commitRequested) {
                $this->consumer->commit($batch->nextOffsets());
            }
        } finally {
            $this->activeBatch = null;
        }
    }

    private function discardPending(): void
    {
        $this->pending = [];
        $this->bytes = 0;
        $this->startedAt = null;
    }
}
