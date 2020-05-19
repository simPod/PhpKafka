<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Consumer;

use DateTimeImmutable;

final class BatchTime
{
    /** @var int */
    public $endMsTimestamp;

    public function __construct(int $timeoutMs, DateTimeImmutable $now)
    {
        $this->reset($timeoutMs, $now);
    }

    public function reset(int $timeoutMs, DateTimeImmutable $now) : void
    {
        $this->endMsTimestamp = $now->getTimestamp() * 1000 + $timeoutMs;
    }
}
