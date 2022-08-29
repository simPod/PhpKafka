<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Clients\Consumer;

use PHPUnit\Framework\TestCase;
use RdKafka\Message;
use SimPod\Kafka\Clients\Consumer\ConsumerRecords;
use SimPod\Kafka\Clients\Consumer\Exception\RecordNotFound;

final class ConsumerRecordsTest extends TestCase
{
    public function testAdd(): void
    {
        $consumerRecords = new ConsumerRecords();
        self::assertTrue($consumerRecords->isEmpty());
        self::assertCount(0, $consumerRecords);

        $message = new Message();
        $consumerRecords->add($message);

        self::assertFalse($consumerRecords->isEmpty());
        self::assertCount(1, $consumerRecords);

        $wasCalled = false;
        $consumerRecords->forEach(
            static function (Message $record) use ($message, &$wasCalled): void {
                self::assertSame($message, $record);
                $wasCalled = true;
            },
        );
        self::assertTrue($wasCalled);

        self::assertSame($message, $consumerRecords->getLast());
    }

    public function testCannotGetLastRecordWhenEmpty(): void
    {
        $this->expectException(RecordNotFound::class);

        $consumerRecords = new ConsumerRecords();
        $consumerRecords->getLast();
    }

    public function testClear(): void
    {
        $consumerRecords = new ConsumerRecords();

        $consumerRecords->add(new Message());
        self::assertCount(1, $consumerRecords);

        $consumerRecords->clear();
        self::assertCount(0, $consumerRecords);
    }
}
