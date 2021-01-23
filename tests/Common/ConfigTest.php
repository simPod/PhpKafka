<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Common;

use Generator;
use PHPUnit\Framework\TestCase;
use SimPod\Kafka\Clients\Consumer\ConsumerConfig;

final class ConfigTest extends TestCase
{
    /**
     * @param mixed $value
     *
     * @dataProvider providerSet
     */
    public function testSet($value, string $expected): void
    {
        $config = new ConsumerConfig();
        $config->set(ConsumerConfig::GROUP_ID_CONFIG, $value);

        self::assertSame($expected, $config->get(ConsumerConfig::GROUP_ID_CONFIG));
    }

    /** @return Generator<array{mixed, string}> */
    public function providerSet(): Generator
    {
        yield [true, 'true'];
        yield [false, 'false'];
        yield ['string', 'string'];
        yield [0, '0'];
    }
}
