<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Producer;

use InvalidArgumentException;

/** A serialized Kafka record. A null value is a tombstone, not an empty string. */
final readonly class ProducerRecord
{
    /** @param array<string, string>|null $headers */
    public function __construct(
        public string $topicName,
        public string|null $value,
        public string|null $key = null,
        public int|null $partition = null,
        public array|null $headers = null,
        public int|null $timestampMs = null,
        public string|null $correlationId = null,
    ) {
        if ($partition !== null && $partition < 0) {
            throw new InvalidArgumentException('Partition must be non-negative or null');
        }
    }
}
