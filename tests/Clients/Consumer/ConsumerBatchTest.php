<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Clients\Consumer;

use PHPUnit\Framework\TestCase;
use RdKafka\Message;
use SimPod\Kafka\Clients\Consumer\ConsumerBatch;
use SimPod\Kafka\Clients\Consumer\ConsumerRecords;

use function iterator_to_array;

use const RD_KAFKA_RESP_ERR_NO_ERROR;

final class ConsumerBatchTest extends TestCase
{
    public function testOffsetsCoverEveryTopicPartitionAndUseNextOffset(): void
    {
        $batch = new ConsumerBatch([
            self::record('first', 0, 4),
            self::record('first', 1, 8),
            self::record('second', 0, 2),
            self::record('first', 0, 7),
            self::record('123', 0, 11),
        ]);

        $offsets = [];
        foreach ($batch->nextOffsets() as $partition) {
            $offsets[] = [$partition->getTopic(), $partition->getPartition(), $partition->getOffset()];
        }

        self::assertSame([['first', 0, 8], ['first', 1, 9], ['second', 0, 3], ['123', 0, 12]], $offsets);
    }

    public function testSnapshotsSurviveAccumulatorClearingAndMessageMutation(): void
    {
        $record = self::record('stable', 0, 10);
        $records = new ConsumerRecords();
        $records->add($record);
        $batch = $records->toBatch();
        $records->clear();
        $record->offset = 50;
        $record->payload = 'changed';

        $firstRead = iterator_to_array($batch);
        $firstRead[0]->offset = 90;
        $firstRead[0]->payload = 'also changed';
        $secondRead = iterator_to_array($batch);

        self::assertCount(1, $batch);
        self::assertSame(10, $secondRead[0]->offset);
        self::assertSame('payload', $secondRead[0]->payload);
        self::assertSame(11, $batch->nextOffsets()[0]->getOffset());
    }

    private static function record(string $topic, int $partition, int $offset): Message
    {
        $message = new Message();
        $message->err = RD_KAFKA_RESP_ERR_NO_ERROR;
        // phpcs:ignore Cdn77.NamingConventions.ValidVariableName -- Native RdKafka field name.
        $message->topic_name = $topic;
        $message->partition = $partition;
        $message->offset = $offset;
        $message->payload = 'payload';

        return $message;
    }
}
