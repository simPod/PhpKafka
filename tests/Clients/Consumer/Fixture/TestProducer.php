<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Clients\Consumer\Fixture;

use SimPod\Kafka\Clients\Producer\KafkaProducerWrapper;
use SimPod\Kafka\Clients\Producer\ProducerConfig;

use function Safe\gethostname;

final class TestProducer
{
    private KafkaProducerWrapper $producer;

    public function __construct()
    {
        $this->producer = new KafkaProducerWrapper(
            $this->getConfig(),
            static function (KafkaProducerWrapper $producer): void {
                $producer->flushMessages(5000);
            },
        );
    }

    /** @param array<string, string>|null $headers */
    public function run(string $topic, string $payload, array|null $headers = null): void
    {
        $this->producer->produce(
            $topic,
            null,
            $payload,
            headers: $headers,
        );
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
