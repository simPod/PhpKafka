<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Clients\Consumer;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\TopicPartition;
use RuntimeException;
use SimPod\Kafka\Clients\Consumer\BatchLimits;
use SimPod\Kafka\Clients\Consumer\ConsumerBatch;
use SimPod\Kafka\Clients\Consumer\ConsumerLoop;
use SimPod\Kafka\Clients\Consumer\Exception\IncompatibleStatus;

use function hrtime;
use function iterator_to_array;
use function usleep;

use const RD_KAFKA_RESP_ERR__ALL_BROKERS_DOWN;
use const RD_KAFKA_RESP_ERR__FATAL;
use const RD_KAFKA_RESP_ERR__TIMED_OUT;
use const RD_KAFKA_RESP_ERR_NO_ERROR;

final class ConsumerLoopTest extends TestCase
{
    public function testNormalStopDrainsPartialBatchAndCommitsExactOffsets(): void
    {
        $native = $this->createMock(KafkaConsumer::class);
        $loop = new ConsumerLoop($native, new NullLogger());
        $calls = 0;
        $native->expects(self::exactly(3))->method('consume')->willReturnCallback(
            static function (int $waitMs) use ($loop, &$calls): Message {
                $calls++;
                if ($calls === 3) {
                    $loop->requestStop();

                    return self::event(RD_KAFKA_RESP_ERR__TIMED_OUT);
                }

                return self::record($calls - 1, $calls === 1 ? 3 : 8);
            },
        );
        $processing = new class {
            public bool $handled = false;
        };
        $native->expects(self::once())->method('commit')->with(self::callback(
            static function (array $offsets) use ($processing): bool {
                /** @var list<TopicPartition> $offsets */
                self::assertTrue($processing->handled, 'Commit must follow processing');
                self::assertCount(2, $offsets);
                self::assertSame(0, $offsets[0]->getPartition());
                self::assertSame(4, $offsets[0]->getOffset());
                self::assertSame(1, $offsets[1]->getPartition());
                self::assertSame(9, $offsets[1]->getOffset());

                return true;
            },
        ));

        $loop->run(new BatchLimits(100, 1000, 10), static function (ConsumerBatch $batch) use ($processing): void {
            self::assertCount(2, $batch);
            $processing->handled = true;
        }, commitAfterProcessing: true);
    }

    public function testHandlerExceptionDoesNotCommitEvenWhenCommitWasRequested(): void
    {
        $native = $this->createMock(KafkaConsumer::class);
        $loop = new ConsumerLoop($native, new NullLogger());
        $native->expects(self::once())->method('consume')->willReturn(self::record(0, 5));
        $native->expects(self::never())->method('commit');
        $failure = new RuntimeException('processing failed');
        $handled = 0;

        try {
            $loop->run(
                new BatchLimits(10, 100, 1),
                static function (ConsumerBatch $batch) use ($loop, $failure, &$handled): void {
                    $handled++;
                    $loop->commitBatch($batch);
                    $loop->requestStop();

                    throw $failure;
                },
                commitAfterProcessing: false,
            );
            self::fail('The handler failure must propagate');
        } catch (RuntimeException $exception) {
            self::assertSame($failure, $exception);
        }

        self::assertSame(1, $handled, 'Failed work must not be flushed again');
    }

    public function testTerminalPollErrorDoesNotFlushPendingWork(): void
    {
        $native = $this->createMock(KafkaConsumer::class);
        $loop = new ConsumerLoop($native, new NullLogger());
        $native->expects(self::exactly(2))->method('consume')->willReturn(
            self::record(0, 5),
            self::event(RD_KAFKA_RESP_ERR__FATAL),
        );
        $native->expects(self::never())->method('commit');

        $this->expectException(IncompatibleStatus::class);
        $loop->run(new BatchLimits(10, 1000, 10), static function (ConsumerBatch $batch): void {
            self::fail('A terminal error must not process a final successful batch');
        }, commitAfterProcessing: true);
    }

    public function testRecoverableTransportEventAllowsLaterProcessing(): void
    {
        $native = $this->createMock(KafkaConsumer::class);
        $loop = new ConsumerLoop($native, new NullLogger());
        $native->expects(self::exactly(2))->method('consume')->willReturn(
            self::event(RD_KAFKA_RESP_ERR__ALL_BROKERS_DOWN),
            self::record(0, 5),
        );
        $native->expects(self::once())->method('commit');

        $loop->run(new BatchLimits(10, 1000, 1), static function (ConsumerBatch $batch) use ($loop): void {
            self::assertSame(6, $batch->nextOffsets()[0]->getOffset());
            $loop->requestStop();
        }, commitAfterProcessing: true);
    }

    public function testSubsecondBatchAgeBoundsPollsByRemainingBudget(): void
    {
        $native = $this->createMock(KafkaConsumer::class);
        $loop = new ConsumerLoop($native, new NullLogger());
        $startedAt = (int) hrtime(true);
        $calls = 0;
        $native->method('consume')->willReturnCallback(static function (int $waitMs) use (&$calls): Message {
            $calls++;
            if ($calls === 1) {
                self::assertSame(1000, $waitMs);
                // Time spent waiting for the first record does not age an empty batch.
                usleep(40000);

                return self::record(0, 0);
            }

            self::assertLessThanOrEqual(30, $waitMs, 'Poll wait must use the remaining batch budget');
            usleep($waitMs * 1000);

            return self::event(RD_KAFKA_RESP_ERR__TIMED_OUT);
        });
        $native->expects(self::once())->method('commit');

        $loop->run(new BatchLimits(1000, 30, 10), static function (ConsumerBatch $batch) use ($loop): void {
            self::assertCount(1, $batch);
            $loop->requestStop();
        }, commitAfterProcessing: true);

        $elapsedMs = ((int) hrtime(true) - $startedAt) / 1_000_000;
        self::assertGreaterThanOrEqual(70, $elapsedMs);
        self::assertLessThan(500, $elapsedMs, 'A subsecond deadline must not wait for the full poll interval');
    }

    public function testByteThresholdAndRetainedBatchesRemainIndependent(): void
    {
        $native = $this->createMock(KafkaConsumer::class);
        $loop = new ConsumerLoop($native, new NullLogger());
        $offset = 0;
        $native->expects(self::exactly(3))->method('consume')->willReturnCallback(
            static function (int $waitMs) use ($loop, &$offset): Message {
                if ($offset === 2) {
                    $loop->requestStop();
                }

                return self::record(0, $offset++);
            },
        );
        $native->expects(self::exactly(2))->method('commit');
        $retained = [];

        $loop->run(
            new BatchLimits(10, 1000, 10, 10),
            static function (ConsumerBatch $batch) use ($loop, &$retained): void {
                $retained[] = $batch;
                if ($batch->nextOffsets()[0]->getOffset() === 2) {
                    return;
                }

                $loop->requestStop();
            },
            commitAfterProcessing: true,
        );

        self::assertCount(2, $retained);
        self::assertCount(2, $retained[0]);
        self::assertSame(0, iterator_to_array($retained[0])[0]->offset);
        self::assertSame(2, $retained[0]->nextOffsets()[0]->getOffset());
        self::assertCount(1, $retained[1]);
    }

    public function testOversizedRecordIsAloneAndDoesNotGrowThePreviousBatch(): void
    {
        $native = $this->createMock(KafkaConsumer::class);
        $loop = new ConsumerLoop($native, new NullLogger());
        $large = self::record(0, 1);
        $large->payload = 'a record exceeding the byte threshold';
        $native->expects(self::exactly(2))->method('consume')->willReturn(self::record(0, 0), $large);
        $native->expects(self::exactly(2))->method('commit');
        $offsets = [];

        $loop->run(
            new BatchLimits(10, 1000, 10, 10),
            static function (ConsumerBatch $batch) use ($loop, &$offsets): void {
                self::assertCount(1, $batch);
                $offsets[] = $batch->nextOffsets()[0]->getOffset();
                if ($batch->nextOffsets()[0]->getOffset() === 2) {
                    $loop->requestStop();
                }
            },
            commitAfterProcessing: true,
        );

        self::assertSame([1, 2], $offsets);
    }

    private static function record(int $partition, int $offset): Message
    {
        $message = self::event(RD_KAFKA_RESP_ERR_NO_ERROR);
        // phpcs:ignore Cdn77.NamingConventions.ValidVariableName -- Native RdKafka field name.
        $message->topic_name = 'records';
        $message->partition = $partition;
        $message->offset = $offset;
        $message->payload = 'value';

        return $message;
    }

    private static function event(int $error): Message
    {
        $message = new Message();
        $message->err = $error;

        return $message;
    }
}
