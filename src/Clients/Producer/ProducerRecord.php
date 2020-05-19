<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Producer;

use InvalidArgumentException;
use function sprintf;

final class ProducerRecord
{
    /** @var string */
    public $topic;

    /** @var int|null */
    public $partition;

    /** @var string */
    public $value;

    /** @var string|null */
    public $key;

    public function __construct(string $topic, ?int $partition, string $value, ?string $key = null)
    {
        if ($partition < 0) {
            throw new InvalidArgumentException(sprintf('Invalid partition: %d. Partition number should always be non-negative or null.', $partition));
        }

        $this->topic     = $topic;
        $this->partition = $partition;
        $this->key       = $key;
        $this->value     = $value;
    }
}
