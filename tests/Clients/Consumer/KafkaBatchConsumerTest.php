<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Clients\Consumer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RuntimeException;
use SimPod\Kafka\Clients\Consumer\BatchLimits;
use SimPod\Kafka\Clients\Consumer\ConsumerBatch;
use SimPod\Kafka\Clients\Consumer\ConsumerRunner;
use SimPod\Kafka\Clients\Producer\KafkaProducerWrapper;
use SimPod\Kafka\Tests\Clients\Consumer\Fixture\TestProducer;

use function gethostname;
use function hrtime;
use function mt_rand;

#[CoversClass(ConsumerRunner::class)]
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

        unset($testProducer);
        $consumer = new ConsumerRunner($this->configure(...));
        $consumer->subscribe([self::Topic]);

        try {
            $consumer->runBatch(
                new BatchLimits(1000, 10000, 90),
                static function (ConsumerBatch $batch) use ($consumer): void {
                    self::assertCount(90, $batch);
                    foreach ($batch as $message) {
                        self::assertSame(self::Payload, $message->payload);
                    }

                    $consumer->requestStop();
                },
            );
        } finally {
            $consumer->close();
        }
    }

    private function configure(Conf $config): void
    {
        $startedAt = (int) hrtime(true);
        $config->set('bootstrap.servers', '127.0.0.1:9092');
        $config->set('client.id', (string) gethostname());
        $config->set('group.id', (string) mt_rand());
        $config->set('auto.offset.reset', 'earliest');
        $config->set('enable.auto.commit', 'false');
        $config->set('statistics.interval.ms', '10');
        $config->setStatsCb(
            static function (KafkaConsumer $consumer, string $statistics) use ($startedAt): void {
                if ((int) hrtime(true) - $startedAt > 15_000_000_000) {
                    throw new RuntimeException('Timed out waiting for the maximum-size consumer batch');
                }
            },
        );
    }
}
