<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Consumer;

use Countable;
use Generator;
use InvalidArgumentException;
use IteratorAggregate;
use RdKafka\Message;
use RdKafka\TopicPartition;

use function count;
use function max;
use function substr;

use const RD_KAFKA_RESP_ERR_NO_ERROR;
use const PHP_INT_MAX;

/** @implements IteratorAggregate<int, Message> */
final class ConsumerBatch implements Countable, IteratorAggregate
{
    /** @var list<Message> */
    private readonly array $records;

    /** @var array<string, array<int, int>> */
    private readonly array $offsets;

    /** @param list<Message> $records */
    public function __construct(array $records)
    {
        $snapshot = [];
        $offsets = [];
        foreach ($records as $record) {
            // phpcs:ignore Cdn77.NamingConventions.ValidVariableName -- Native RdKafka field name.
            $topic = $record->topic_name;
            if (
                $record->err !== RD_KAFKA_RESP_ERR_NO_ERROR
                || $topic === null
                || $record->partition < 0
                || $record->offset < 0
                || $record->offset === PHP_INT_MAX
            ) {
                throw new InvalidArgumentException('A batch can contain only successful records with concrete offsets');
            }

            $snapshot[] = clone $record;
            // Prefix topic keys so PHP does not convert numeric topic names to integer keys.
            $topicKey = 'topic:' . $topic;
            $offsets[$topicKey][$record->partition] = max(
                $offsets[$topicKey][$record->partition] ?? 0,
                $record->offset + 1,
            );
        }

        $this->records = $snapshot;
        $this->offsets = $offsets;
    }

    /** @return Generator<int, Message> */
    public function getIterator(): Generator
    {
        foreach ($this->records as $record) {
            yield clone $record;
        }
    }

    public function count(): int
    {
        return count($this->records);
    }

    /** @return list<TopicPartition> */
    public function nextOffsets(): array
    {
        $partitions = [];
        foreach ($this->offsets as $topic => $offsets) {
            foreach ($offsets as $partition => $offset) {
                $partitions[] = new TopicPartition(substr($topic, 6), $partition, $offset);
            }
        }

        return $partitions;
    }
}
