<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Clients\Consumer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RdKafka\Message;
use SimPod\Kafka\Clients\Consumer\ConsumerConfig;
use SimPod\Kafka\Clients\Consumer\ConsumerRecords;
use SimPod\Kafka\Clients\Consumer\KafkaConsumer;
use SimPod\Kafka\Clients\Producer\KafkaProducerWrapper;
use SimPod\Kafka\Tests\Clients\Consumer\Fixture\TestProducer;

use function mt_rand;
use function Safe\gethostname;

#[CoversClass(KafkaConsumer::class)]
#[CoversClass(KafkaProducerWrapper::class)]
final class KafkaBatchConsumerTest extends TestCase
{
    public const string Payload = 'Tasty, chilled pudding is best flavored with juicy lime.';
    public const string Topic = 'kafka-batch-consumer';

    public function testMaxBatchSize(): void
    {
        $testProducer = new TestProducer();
        for ($i = 0; $i < 100; $i++) {
            $testProducer->run(self::Topic, self::Payload);
        }

        $consumer = new KafkaConsumer($this->getConfig());
        $consumer->subscribe([self::Topic]);

        $consumer->startBatch(
            90,
            10000,
            static function (Message $message): void {
                self::assertSame(self::Payload, $message->payload);
            },
            static function (ConsumerRecords $consumerRecords) use ($consumer): void {
                self::assertCount(90, $consumerRecords);

                $consumer->shutdown();
            },
        );
    }

    private function getConfig(): ConsumerConfig
    {
        $consumerConfig = new ConsumerConfig();
        $consumerConfig->set(ConsumerConfig::BOOTSTRAP_SERVERS_CONFIG, '127.0.0.1:9092');
        $consumerConfig->set(ConsumerConfig::CLIENT_ID_CONFIG, gethostname());
        $consumerConfig->set(ConsumerConfig::GROUP_ID_CONFIG, mt_rand());
        $consumerConfig->set(ConsumerConfig::AUTO_OFFSET_RESET_CONFIG, 'earliest');

        return $consumerConfig;
    }
}
