<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Consumer;

use InvalidArgumentException;

use function intdiv;

use const PHP_INT_MAX;

final readonly class BatchLimits
{
    public function __construct(
        public int $pollWaitMs,
        public int $maxAgeMs,
        public int $maxRecords,
        public int|null $maxBytes = null,
    ) {
        if ($pollWaitMs < 1 || $maxAgeMs < 1 || $maxRecords < 1 || ($maxBytes !== null && $maxBytes < 1)) {
            throw new InvalidArgumentException('Batch limits must be positive');
        }

        if ($pollWaitMs > 2_147_483_647) {
            throw new InvalidArgumentException('Poll wait exceeds the native signed 32-bit millisecond range');
        }

        if ($maxAgeMs > intdiv(PHP_INT_MAX, 1_000_000)) {
            throw new InvalidArgumentException('Batch age exceeds the monotonic clock range');
        }
    }
}
