<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Consumer;

use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RdKafka\KafkaConsumer as RdKafkaConsumer;
use RdKafka\Message;

/** Native compatibility adapter. Prefer ConsumerRunner for managed group subscriptions. */
final class KafkaConsumer extends RdKafkaConsumer
{
    private readonly ConsumerLoop $loop;

    private readonly bool $autoCommit;

    public function __construct(ConsumerConfig $config, LoggerInterface|null $logger = null)
    {
        $this->autoCommit = $config->get('enable.auto.commit') !== 'false';
        // Preserve all user callbacks and let librdkafka handle ordinary group assignment.

        parent::__construct($config->getConf());

        $this->loop = new ConsumerLoop($this, $logger ?? new NullLogger());
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
        $this->loop->run(
            new BatchLimits($timeoutMs, $timeoutMs, 1),
            static function (ConsumerBatch $batch) use ($onSuccess): void {
                foreach ($batch as $message) {
                    $onSuccess($message);
                }
            },
            false,
            $onPartitionEof,
            $onTimedOut,
        );
    }

    /**
     * @deprecated Use ConsumerRunner::runBatch() for group subscriptions.
     *
     * @param (callable(Message):void)|null $processRecord
     * @param (callable(ConsumerRecords):void)|null $onBatchProcessed
     */
    public function startBatch(
        int $maxBatchSize,
        int $timeoutMs,
        callable|null $processRecord = null,
        callable|null $onBatchProcessed = null,
    ): void {
        if ($processRecord === null && $onBatchProcessed === null) {
            throw new InvalidArgumentException('A batch processing handler is required');
        }

        if ($this->autoCommit) {
            throw new InvalidArgumentException('Legacy batching requires enable.auto.commit=false');
        }

        if ($this->getSubscription() !== []) {
            throw new LogicException('Subscribed batching requires ConsumerRunner to own the rebalance callback');
        }

        if ($this->getAssignment() === []) {
            throw new LogicException('Legacy batching requires a fixed manual assignment');
        }

        $this->loop->run(
            new BatchLimits($timeoutMs, $timeoutMs, $maxBatchSize),
            static function (ConsumerBatch $batch) use ($processRecord, $onBatchProcessed): void {
                $records = new ConsumerRecords();
                foreach ($batch as $message) {
                    $records->add($message);
                    if ($processRecord !== null) {
                        $processRecord($message);
                    }
                }

                if ($onBatchProcessed !== null) {
                    $onBatchProcessed($records);
                }
            },
            false,
        );
    }

    public function commitBatch(ConsumerBatch|ConsumerRecords $batch): void
    {
        if ($batch->count() === 0) {
            return;
        }

        $this->commit(($batch instanceof ConsumerRecords ? $batch->toBatch() : $batch)->nextOffsets());
    }

    public function requestStop(): void
    {
        $this->loop->requestStop();
    }

    public function shutdown(): void
    {
        $this->requestStop();
    }

    public function stop(): void
    {
        $this->requestStop();
    }
}
