<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Clients\Consumer;

use SimPod\Kafka\Clients\Producer\KafkaProducer;
use SimPod\Kafka\Clients\Producer\ProducerConfig;
use SimPod\Kafka\Clients\Producer\ProducerRecord;
use function gethostname;

final class TestProducer
{
    /** @var KafkaProducer */
    private $producer;

    public function __construct()
    {
        $this->producer = new KafkaProducer($this->getConfig());
    }

    public function run(string $payload) : void
    {
        $record = new ProducerRecord(KafkaBatchConsumerTest::TOPIC, null, null, $payload);
        $this->producer->produce($record);
    }

    private function getConfig() : ProducerConfig
    {
        $config = new ProducerConfig();
        $config->set(ProducerConfig::CLIENT_ID_CONFIG, gethostname());
        $config->set(ProducerConfig::BOOTSTRAP_SERVERS_CONFIG, '127.0.0.1:9092');
        $config->set(ProducerConfig::ACKS_CONFIG, 'all');

        return $config;
    }

    public function __destruct()
    {
        $this->producer->flush(5000);
    }
}
