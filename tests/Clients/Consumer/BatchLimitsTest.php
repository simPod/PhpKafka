<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Clients\Consumer;

use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimPod\Kafka\Clients\Consumer\BatchLimits;

use const PHP_INT_MAX;

final class BatchLimitsTest extends TestCase
{
    #[DataProvider('provideInvalidBounds')]
    public function testInvalidBounds(int $pollWaitMs, int $maxAgeMs, int $maxRecords, int|null $maxBytes): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BatchLimits($pollWaitMs, $maxAgeMs, $maxRecords, $maxBytes);
    }

    /** @phpstan-return Generator<string, array{int, int, int, int|null}> */
    public static function provideInvalidBounds(): Generator
    {
        yield 'zero poll' => [0, 1, 1, null];
        yield 'negative poll' => [-1, 1, 1, null];
        yield 'native poll overflow' => [2_147_483_648, 1, 1, null];
        yield 'zero age' => [1, 0, 1, null];
        yield 'negative age' => [1, -1, 1, null];
        yield 'age overflow' => [1, PHP_INT_MAX, 1, null];
        yield 'zero records' => [1, 1, 0, null];
        yield 'negative records' => [1, 1, -1, null];
        yield 'zero bytes' => [1, 1, 1, 0];
        yield 'negative bytes' => [1, 1, 1, -1];
    }
}
