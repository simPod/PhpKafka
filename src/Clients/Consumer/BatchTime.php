<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Consumer;

use DateTimeImmutable;

/** @deprecated Retained for compatibility. ConsumerRunner uses monotonic BatchLimits deadlines. */
final class BatchTime
{
    public int $endMsTimestamp;

    public function __construct(int $timeoutMs, DateTimeImmutable $now)
    {
        $this->reset($timeoutMs, $now);
    }

    public function reset(int $timeoutMs, DateTimeImmutable $now): void
    {
        $this->endMsTimestamp = $now->getTimestamp() * 1000 + $timeoutMs;
    }
}
