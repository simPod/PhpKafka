<?php

declare(strict_types=1);

namespace Clients\Consumer;

use Exception;
use PHPUnit\Framework\TestCase;
use SimPod\Kafka\Clients\Consumer\ConsumerConfig;
use SimPod\Kafka\Clients\Consumer\KafkaConsumer;
use SimPod\Kafka\Tests\Clients\Consumer\TestProducer;

use function mt_rand;
use function Safe\gethostname;

use const RD_KAFKA_RESP_ERR__PARTITION_EOF;
use const RD_KAFKA_RESP_ERR__TIMED_OUT;
use const RD_KAFKA_RESP_ERR_NO_ERROR;

final class KafkaTest extends TestCase
{
    public function testProduceConsume(): void
    {
        $topic   = 'produce-consume';
        $headers = ['key' => 'value'];

        $testProducer = new TestProducer();
        $testProducer->run($topic, 'test', $headers);

        unset($testProducer);

        $consumer = new KafkaConsumer($this->getConfig());
        $consumer->subscribe([$topic]);

        while (true) {
            $message = $consumer->consume(5000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    self::assertSame($message->headers, $headers);

                    break 2;
                // phpcs:ignore PSR2.ControlStructures.SwitchDeclaration.TerminatingComment
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    self::fail('No more messages; will wait for more');
                // phpcs:ignore PSR2.ControlStructures.SwitchDeclaration.TerminatingComment
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    self::fail('Timed out');
                default:
                    throw new Exception($message->errstr(), $message->err);
            }
        }
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
