<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Producer\Exception;

use RuntimeException;
use Throwable;

final class DeliveryFailed extends RuntimeException
{
    public readonly int $errorCode;

    public function __construct(
        string $message,
        int $code = 0,
        Throwable|null $previous = null,
        public readonly string|null $topicName = null,
    ) {
        $this->errorCode = $code;

        parent::__construct($message, $code, $previous);
    }
}
