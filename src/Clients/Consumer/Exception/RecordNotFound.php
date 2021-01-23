<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Consumer\Exception;

use SimPod\Kafka\Common\Exception\KafkaException;

final class RecordNotFound extends KafkaException
{
    public static function setEmpty(): self
    {
        return new self('Record was not found because record set is empty');
    }
}
