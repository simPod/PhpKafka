<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Clients\Consumer;

use Closure;
use Countable;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\Producer;
use RdKafka\TopicPartition;
use RuntimeException;
use SimPod\Kafka\Clients\Consumer\BatchLimits;
use SimPod\Kafka\Clients\Consumer\ConsumerBatch;
use SimPod\Kafka\Clients\Consumer\ConsumerConfig;
use SimPod\Kafka\Clients\Consumer\ConsumerRunner;
use SimPod\Kafka\Clients\Consumer\KafkaConsumer as CompatibilityConsumer;
use WeakReference;

use function bin2hex;
use function count;
use function hrtime;
use function method_exists;
use function random_bytes;

use const RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS;
use const RD_KAFKA_RESP_ERR_NO_ERROR;

final class ConsumerRunnerBrokerTest extends TestCase
{
    // librdkafka's RD_KAFKA_OFFSET_INVALID is not exported as a PHP constant by ext-rdkafka 6.x.
    private const int UncommittedOffset = -1001;

    public function testCommitBatchPersistsBothTopicsAndNotJustTheLastRecord(): void
    {
        $topics = [self::uniqueName(), self::uniqueName()];
        foreach ($topics as $topic) {
            self::publish($topic);
        }

        $configure = self::configure();
        $runner = new ConsumerRunner($configure);
        $runner->subscribe($topics);
        try {
            $runner->runBatch(
                new BatchLimits(100, 10000, 2),
                static function (ConsumerBatch $batch) use ($runner): void {
                    self::assertCount(2, $batch);
                    $runner->commitBatch($batch);
                    $runner->requestStop();
                },
                commitAfterProcessing: false,
            );
        } finally {
            $runner->close();
        }

        $config = new Conf();
        $configure($config);
        $observer = new KafkaConsumer($config);
        try {
            $offsets = $observer->getCommittedOffsets([
                new TopicPartition($topics[0], 0),
                new TopicPartition($topics[1], 0),
            ], 5000);
            self::assertSame(1, $offsets[0]->getOffset());
            self::assertSame(1, $offsets[1]->getOffset());
        } finally {
            $observer->close();
        }
    }

    #[DataProvider('provideRevocationDrainsBeforeOwnershipIsReleased')]
    public function testRevocationDrainsBeforeOwnershipIsReleased(string $strategy, bool $failProcessing): void
    {
        if (
            $strategy === 'cooperative-sticky'
            && (! method_exists(KafkaConsumer::class, 'incrementalAssign')
                || ! method_exists(KafkaConsumer::class, 'incrementalUnassign'))
        ) {
            self::markTestSkipped('The installed native build does not support cooperative assignment');
        }

        $topic = self::uniqueName();
        self::publish($topic);
        $configure = self::configure();
        $startedAt = (int) hrtime(true);
        $left = false;
        $statsCallback =
            static function (KafkaConsumer $consumer, string $statistics) use ($startedAt, &$left): void {
                if ((int) hrtime(true) - $startedAt > 15_000_000_000) {
                    throw new RuntimeException('Timed out waiting for the real broker revocation');
                }

                if ($left) {
                    return;
                }

                $assignment = $consumer->getAssignment();
                if ($assignment === []) {
                    return;
                }

                $positions = $consumer->getOffsetPositions($assignment);
                if ($positions[0]->getOffset() < 1) {
                    return;
                }

                $left = true;
                // A real native group leave queues revocation with a pending partial batch.
                $consumer->unsubscribe();
            };
        $runner = new ConsumerRunner(
            static function (Conf $config) use ($configure, $strategy, $statsCallback): void {
                $configure($config);
                $config->set('partition.assignment.strategy', $strategy);
                $config->set('statistics.interval.ms', '10');
                $config->setStatsCb($statsCallback);
            },
        );
        $runner->subscribe([$topic]);
        $failure = new RuntimeException('revocation processing failed');
        $handled = 0;
        try {
            $runner->runBatch(
                new BatchLimits(100, 30000, 100),
                static function (ConsumerBatch $batch) use (
                    $runner,
                    $failProcessing,
                    $failure,
                    &$left,
                    &$handled,
                ): void {
                    self::assertTrue($left, 'Processing must be triggered by revocation, not the batch deadline');
                    self::assertCount(1, $batch);
                    $handled++;
                    $runner->commitBatch($batch);
                    $runner->requestStop();
                    if ($failProcessing) {
                        throw $failure;
                    }
                },
            );
            self::assertFalse($failProcessing, 'A processing failure must escape the native callback');
        } catch (RuntimeException $exception) {
            self::assertTrue($failProcessing);
            self::assertSame($failure, $exception);
        } finally {
            $runner->close();
        }

        self::assertSame(1, $handled, 'Revoked work must not be processed again during close');
        $config = new Conf();
        $configure($config);
        $observer = new KafkaConsumer($config);
        try {
            $offsets = $observer->getCommittedOffsets([new TopicPartition($topic, 0)], 5000);
            self::assertSame($failProcessing ? self::UncommittedOffset : 1, $offsets[0]->getOffset());
        } finally {
            $observer->close();
        }
    }

    /** @phpstan-return Generator<string, array{string, bool}> */
    public static function provideRevocationDrainsBeforeOwnershipIsReleased(): Generator
    {
        foreach (['range', 'roundrobin', 'cooperative-sticky'] as $strategy) {
            yield $strategy . ' success' => [$strategy, false];
            yield $strategy . ' failure' => [$strategy, true];
        }
    }

    public function testCooperativePartialRevocationDrainsAlsoRetainedPartition(): void
    {
        if (
            ! method_exists(KafkaConsumer::class, 'incrementalAssign')
            || ! method_exists(KafkaConsumer::class, 'incrementalUnassign')
        ) {
            self::markTestSkipped('The installed native build does not support cooperative assignment');
        }

        $topics = [self::uniqueName(), self::uniqueName()];
        foreach ($topics as $topic) {
            self::publish($topic);
        }

        $configure = self::configure();
        $changed = false;
        $observed = new class {
            /** @var WeakReference<KafkaConsumer>|null */
            public WeakReference|null $consumer = null;
        };
        $startedAt = (int) hrtime(true);
        $runner = new ConsumerRunner(
            static function (Conf $config) use ($configure, $topics, $startedAt, &$changed, $observed): void {
                $configure($config);
                $config->set('partition.assignment.strategy', 'cooperative-sticky');
                $config->set('statistics.interval.ms', '10');
                $config->setStatsCb(
                    static function (
                        KafkaConsumer $consumer,
                        string $statistics,
                    ) use (
                        $topics,
                        $startedAt,
                        &$changed,
                        $observed,
                    ): void {
                        $observed->consumer = WeakReference::create($consumer);
                        if ((int) hrtime(true) - $startedAt > 15_000_000_000) {
                            throw new RuntimeException('Timed out waiting for a partial cooperative revocation');
                        }

                        if ($changed) {
                            return;
                        }

                        $assignment = $consumer->getAssignment();
                        if (count($assignment) !== 2) {
                            return;
                        }

                        foreach ($consumer->getOffsetPositions($assignment) as $position) {
                            if ($position->getOffset() < 1) {
                                return;
                            }
                        }

                        $changed = true;
                        $consumer->subscribe([$topics[1]]);
                    },
                );
            },
        );
        $runner->subscribe($topics);
        try {
            $runner->runBatch(
                new BatchLimits(100, 30000, 100),
                static function (ConsumerBatch $batch) use ($runner, &$changed): void {
                    self::assertTrue($changed);
                    self::assertCount(2, $batch, 'Drain both revoked and still-owned records');
                    $runner->requestStop();
                },
            );
            $native = $observed->consumer?->get();
            self::assertInstanceOf(KafkaConsumer::class, $native);
            $assignment = $native->getAssignment();
            self::assertCount(1, $assignment, 'Only revoked partitions must be incrementally released');
            self::assertSame($topics[1], $assignment[0]->getTopic());
            $offsets = $native->getCommittedOffsets([
                new TopicPartition($topics[0], 0),
                new TopicPartition($topics[1], 0),
            ], 5000);
            self::assertSame(1, $offsets[0]->getOffset());
            self::assertSame(1, $offsets[1]->getOffset());
        } finally {
            $runner->close();
        }
    }

    public function testReusableInitializerDoesNotLoseItsNativeCallbacks(): void
    {
        $topic = self::uniqueName();
        self::publish($topic);
        $configure = self::configure();
        $assignmentState = new class implements Countable {
            /** @var non-negative-int */
            private int $assignments = 0;

            public function recordAssignment(): void
            {
                $this->assignments++;
            }

            public function count(): int
            {
                return $this->assignments;
            }
        };
        $initialize = static function (Conf $config) use ($configure, $assignmentState): void {
            $configure($config);
            $config->setRebalanceCb(
                static function (
                    KafkaConsumer $consumer,
                    int $error,
                    array|null $partitions = null,
                ) use ($assignmentState): void {
                    if ($error === RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS) {
                        $assignmentState->recordAssignment();
                        $consumer->assign($partitions);
                    } else {
                        $consumer->assign();
                    }
                },
            );
        };
        $runner = new ConsumerRunner($initialize);
        $runner->subscribe([$topic]);
        try {
            $runner->run(static function () use ($runner): void {
                $runner->requestStop();
            }, 100);
        } finally {
            $runner->close();
        }

        self::assertCount(0, $assignmentState);
        $config = new ConsumerConfig();
        $initialize($config->getConf());
        $native = new CompatibilityConsumer($config);
        $native->subscribe([$topic]);
        try {
            for ($attempt = 0; $attempt < 100 && $assignmentState->count() === 0; $attempt++) {
                $native->consume(100);
            }

            self::assertCount(
                1,
                $assignmentState,
                'A reusable initializer must keep native callback setup intact',
            );
        } finally {
            $native->close();
        }
    }

    /** @return Closure(Conf):void */
    private static function configure(): Closure
    {
        $group = self::uniqueName();

        return static function (Conf $config) use ($group): void {
            $startedAt = (int) hrtime(true);
            $config->set('bootstrap.servers', '127.0.0.1:9092');
            $config->set('group.id', $group);
            $config->set('enable.auto.commit', 'false');
            $config->set('auto.offset.reset', 'earliest');
            $config->set('statistics.interval.ms', '10');
            $config->setStatsCb(
                static function (KafkaConsumer $consumer, string $statistics) use ($startedAt): void {
                    if ((int) hrtime(true) - $startedAt > 15_000_000_000) {
                        throw new RuntimeException('Timed out waiting for consumer broker test progress');
                    }
                },
            );
        };
    }

    private static function publish(string $topic): void
    {
        $config = new Conf();
        $config->set('bootstrap.servers', '127.0.0.1:9092');
        $config->set('acks', 'all');
        $delivered = false;
        $config->setDrMsgCb(static function (Producer $producer, Message $message) use (&$delivered): void {
            self::assertSame(RD_KAFKA_RESP_ERR_NO_ERROR, $message->err);
            $delivered = true;
        });
        $producer = new Producer($config);
        $producer->newTopic($topic)->produce(0, 0, 'payload');
        self::assertSame(RD_KAFKA_RESP_ERR_NO_ERROR, $producer->flush(5000));
        self::assertTrue($delivered, 'The broker must confirm the fixture record');
    }

    private static function uniqueName(): string
    {
        return 'consumer-contract-' . bin2hex(random_bytes(6));
    }
}
