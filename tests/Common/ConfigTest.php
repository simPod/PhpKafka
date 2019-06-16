<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Common;

use PHPUnit\Framework\TestCase;
use SimPod\Kafka\Clients\Consumer\ConsumerConfig;

final class ConfigTest extends TestCase
{
    /** @var ConsumerConfig */
    private $config;

    public function setUp() : void
    {
        $config = new ConsumerConfig();
        $config->set(ConsumerConfig::ENABLE_AUTO_COMMIT_CONFIG, true);
        $this->config = $config;

        parent::setUp();
    }

    public function testGetInt() : void
    {
        self::assertSame(0, $this->config->getInt(ConsumerConfig::ENABLE_AUTO_COMMIT_CONFIG));
    }

    public function testGetString() : void
    {
        self::assertSame('true', $this->config->getString(ConsumerConfig::ENABLE_AUTO_COMMIT_CONFIG));
    }

    public function testGetBool() : void
    {
        self::assertTrue($this->config->getBool(ConsumerConfig::ENABLE_AUTO_COMMIT_CONFIG));
    }
}
