<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Clients\Consumer;

use SimPod\Kafka\Clients\Producer\KafkaProducer;
use SimPod\Kafka\Clients\Producer\ProducerConfig;

use function Safe\gethostname;

final class TestProducer
{
    private KafkaProducer $producer;

    public function __construct()
    {
        $this->producer = new KafkaProducer(
            $this->getConfig(),
            static function (KafkaProducer $producer): void {
                $producer->flushMessages(5000);
            }
        );
    }

    public function run(string $payload): void
    {
        $this->producer->produce(KafkaBatchConsumerTest::TOPIC, null, $payload);
    }

    private function getConfig(): ProducerConfig
    {
        $config = new ProducerConfig();
        $config->set(ProducerConfig::CLIENT_ID_CONFIG, gethostname());
        $config->set(ProducerConfig::BOOTSTRAP_SERVERS_CONFIG, '127.0.0.1:9092');
        $config->set(ProducerConfig::ACKS_CONFIG, 'all');

        return $config;
    }
}
