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

    /** @var mixed */
    public $key;

    /** @var mixed */
    public $value;

    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function __construct(string $topic, ?int $partition, $key, $value)
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
