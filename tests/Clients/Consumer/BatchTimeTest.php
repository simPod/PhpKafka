<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Clients\Consumer;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SimPod\Kafka\Clients\Consumer\BatchTime;

final class BatchTimeTest extends TestCase
{
    public function testReset() : void
    {
        $moment = new DateTimeImmutable('2020-01-01 00:00:00');
        $batchTime = new BatchTime(1, $moment);

        $batchTime->reset(2, $moment);

        self::assertSame(1577836800002, $batchTime->endMsTimestamp);
    }
}
