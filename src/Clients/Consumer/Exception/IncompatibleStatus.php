<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Consumer\Exception;

use RdKafka\Message;
use SimPod\Kafka\Common\Exception\KafkaException;

use function sprintf;

final class IncompatibleStatus extends KafkaException
{
    public static function fromMessage(Message $message): self
    {
        return new self(
            sprintf(
                'Consumer status "%d" is not handled: %s',
                $message->err,
                $message->errstr()
            )
        );
    }
}
