<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Clients\Consumer;

use PHPUnit\Framework\TestCase;
use RdKafka\Message;
use SimPod\Kafka\Clients\Consumer\ConsumerConfig;
use SimPod\Kafka\Clients\Consumer\ConsumerRecords;
use SimPod\Kafka\Clients\Consumer\KafkaConsumer;
use function gethostname;
use function microtime;
use function mt_rand;

final class KafkaBatchConsumerTest extends TestCase
{
    public const PAYLOAD = 'Tasty, chilled pudding is best flavored with juicy lime.';
    public const TOPIC   = 'kafka-batch-consumer';

    public function testMaxBatchSize() : void
    {
        $testProducer = new TestProducer();
        for ($i = 0; $i < 90; $i++) {
            $testProducer->run(self::PAYLOAD);
        }

        $consumer = new KafkaConsumer($this->getConfig());
        $consumer->subscribe([self::TOPIC]);

        $consumer->startBatch(
            static function (Message $message) : void {
                self::assertSame(self::PAYLOAD, $message->payload);
            },
            static function (ConsumerRecords $consumerRecords) use ($consumer) : void {
                self::assertCount(90, $consumerRecords);

                $consumer->shutdown();
            },
            90
        );
    }

    public function testTimeout() : void
    {
        $testProducer = new TestProducer();
        for ($i = 0; $i < 100; $i++) {
            $testProducer->run(self::PAYLOAD);
        }

        $consumer = new KafkaConsumer($this->getConfig());
        $consumer->subscribe([self::TOPIC]);

        $start = microtime(true);
        $consumer->startBatch(
            static function (Message $message) : void {
            },
            static function (ConsumerRecords $consumerRecords) use ($consumer, $start) : void {
                $end = microtime(true);

                self::assertGreaterThanOrEqual(10, $end - $start);
                self::assertLessThan(12, $end - $start);

                self::assertGreaterThan(0, $consumerRecords->count());

                $consumer->shutdown();
            },
            null,
            10000
        );
    }

    private function getConfig() : ConsumerConfig
    {
        $consumerConfig = new ConsumerConfig();
        $consumerConfig->set(ConsumerConfig::BOOTSTRAP_SERVERS_CONFIG, '127.0.0.1:9092');
        $consumerConfig->set(ConsumerConfig::CLIENT_ID_CONFIG, gethostname());
        $consumerConfig->set(ConsumerConfig::GROUP_ID_CONFIG, mt_rand());
        $consumerConfig->set(ConsumerConfig::AUTO_OFFSET_RESET_CONFIG, 'earliest');

        return $consumerConfig;
    }
}
