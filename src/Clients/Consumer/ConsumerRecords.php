<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Consumer;

use Countable;
use RdKafka\Message;
use SimPod\Kafka\Clients\Consumer\Exception\RecordNotFound;

use function array_key_last;
use function count;

final class ConsumerRecords implements Countable
{
    /** @var Message[] */
    private array $records = [];

    public function add(Message $record): void
    {
        $this->records[] = $record;
    }

    /** @param callable(Message $record) : void $action */
    public function forEach(callable $action): void
    {
        foreach ($this->records as $record) {
            $action($record);
        }
    }

    public function count(): int
    {
        return count($this->records);
    }

    public function isEmpty(): bool
    {
        return count($this->records) === 0;
    }

    public function clear(): void
    {
        $this->records = [];
    }

    public function getLast(): Message
    {
        $lastRecord = $this->records[array_key_last($this->records)] ?? null;
        if ($lastRecord === null) {
            throw RecordNotFound::setEmpty();
        }

        return $lastRecord;
    }
}
