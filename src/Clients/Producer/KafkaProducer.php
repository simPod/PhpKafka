<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Producer;

use RdKafka\Producer;
use const RD_KAFKA_PARTITION_UA;

class KafkaProducer extends Producer
{
    private const RD_KAFKA_MSG_F_COPY = 0;

    public function __construct(ProducerConfig $config)
    {
        parent::__construct($config->getConf());
    }

    public function produce(ProducerRecord $record) : void
    {
        $topic = $this->newTopic($record->topic);
        $topic->produce($record->partition ?? RD_KAFKA_PARTITION_UA, self::RD_KAFKA_MSG_F_COPY, $record->value, $record->key);
        $this->poll(0);
    }
}
