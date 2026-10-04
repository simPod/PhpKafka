<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Consumer;

use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\TopicPartition;
use RuntimeException;
use WeakReference;

use function count;
use function explode;
use function in_array;
use function method_exists;
use function trim;

use const RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS;
use const RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS;

final class ConsumerRunner
{
    private readonly KafkaConsumer $consumer;

    private readonly ConsumerLoop $loop;

    private bool $closed = false;

    /**
     * Creates a fresh native configuration and owns its rebalance callback.
     * enable.auto.commit must be false: this runner commits only successfully processed work.
     *
     * @param callable(Conf):void $configure Other native callbacks can be installed here.
     */
    public function __construct(callable $configure, LoggerInterface|null $logger = null)
    {
        $config = new Conf();
        $configure($config);
        $settings = $config->dump();
        if (($settings['enable.auto.commit'] ?? 'true') !== 'false') {
            throw new InvalidArgumentException('ConsumerRunner requires enable.auto.commit=false');
        }

        if (($settings['group.protocol'] ?? 'classic') !== 'classic') {
            throw new InvalidArgumentException('ConsumerRunner supports only the classic group protocol');
        }

        $strategies = explode(',', $settings['partition.assignment.strategy'] ?? 'range,roundrobin');
        $cooperative = false;
        foreach ($strategies as $strategy) {
            $strategy = trim($strategy);
            if (! in_array($strategy, ['range', 'roundrobin', 'cooperative-sticky'], true)) {
                throw new InvalidArgumentException('ConsumerRunner supports range, roundrobin or cooperative-sticky');
            }

            $cooperative = $cooperative || $strategy === 'cooperative-sticky';
        }

        if ($cooperative && count($strategies) !== 1) {
            throw new InvalidArgumentException('Cooperative and eager assignment strategies cannot be mixed');
        }

        if (
            $cooperative
            && (! method_exists(KafkaConsumer::class, 'incrementalAssign')
                || ! method_exists(KafkaConsumer::class, 'incrementalUnassign'))
        ) {
            throw new InvalidArgumentException('This ext-rdkafka build does not support cooperative assignment');
        }

        $reference = WeakReference::create($this);
        $config->setRebalanceCb(
            static function (KafkaConsumer $consumer, int $error, array|null $partitions = null) use (
                $cooperative,
                $reference,
            ): void {
                /** @var list<TopicPartition>|null $partitions */
                if ($error === RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS) {
                    if ($cooperative) {
                        $consumer->incrementalAssign($partitions ?? []);
                    } else {
                        $consumer->assign($partitions);
                    }

                    return;
                }

                try {
                    $runner = $reference->get();
                    if ($runner === null) {
                        // Native teardown can still require unassignment after the runner has gone.
                        return;
                    }

                    if ($error !== RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS) {
                        $runner->loop->requestStop();

                        throw new RuntimeException('Unexpected native rebalance error', $error);
                    }

                    // Drain the entire pending batch, including still-owned cooperative partitions.
                    $runner->loop->flush();
                } finally {
                    if ($cooperative) {
                        $consumer->incrementalUnassign($partitions ?? []);
                    } else {
                        $consumer->assign();
                    }
                }
            },
        );

        $this->consumer = new KafkaConsumer($config);
        $this->loop = new ConsumerLoop($this->consumer, $logger ?? new NullLogger());
    }

    /** @param list<string> $topics */
    public function subscribe(array $topics): void
    {
        $this->assertIdle();
        if ($topics === []) {
            throw new InvalidArgumentException('A subscription requires at least one topic');
        }

        $this->consumer->subscribe($topics);
    }

    /** @param callable(Message):void $handler */
    public function run(callable $handler, int $pollWaitMs = 1000): void
    {
        $this->runBatch(
            new BatchLimits($pollWaitMs, $pollWaitMs, 1),
            static function (ConsumerBatch $batch) use ($handler): void {
                foreach ($batch as $message) {
                    $handler($message);
                }
            },
        );
    }

    /** @param callable(ConsumerBatch):void $handler */
    public function runBatch(BatchLimits $limits, callable $handler, bool $commitAfterProcessing = true): void
    {
        $this->assertIdle();
        if ($this->consumer->getSubscription() === []) {
            throw new LogicException('Subscribe before starting the consumer runner');
        }

        $this->loop->run($limits, $handler, $commitAfterProcessing);
    }

    /** Request a synchronous commit of this batch after its handler returns successfully. */
    public function commitBatch(ConsumerBatch $batch): void
    {
        $this->loop->commitBatch($batch);
    }

    public function requestStop(): void
    {
        $this->loop->requestStop();
    }

    /** Close explicitly after run returns, normally from the application's finally block. */
    public function close(): void
    {
        if ($this->closed) {
            return;
        }

        $this->assertIdle();
        $this->closed = true;
        $this->consumer->close();
    }

    private function assertIdle(): void
    {
        if ($this->closed || $this->loop->isRunning()) {
            throw new LogicException('The consumer is closed or a run is active');
        }
    }
}
