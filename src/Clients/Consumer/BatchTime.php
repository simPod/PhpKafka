<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Consumer;

use DateTimeImmutable;

final class BatchTime
{
    /** @var int */
    public $endTime;

    public function __construct(int $timeoutMs)
    {
        $this->reset($timeoutMs);
    }

    public function reset(int $timeoutMs) : void
    {
        $this->endTime = (new DateTimeImmutable())->getTimestamp() * 1000 + $timeoutMs;
    }
}
