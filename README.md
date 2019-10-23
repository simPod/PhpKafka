# PHP Kafka boilerplate wrapper around RdKafka

[![Build Status](https://travis-ci.org/simPod/Kafka.svg?branch=master)](https://travis-ci.org/simPod/Kafka)
[![Downloads](https://poser.pugx.org/simpod/kafka/d/total.svg)](https://packagist.org/packages/simpod/kafka)
[![Packagist](https://poser.pugx.org/simpod/kafka/v/stable.svg)](https://packagist.org/packages/simpod/kafka)
[![Licence](https://poser.pugx.org/simpod/kafka/license.svg)](https://packagist.org/packages/simpod/kafka)
[![Quality Score](https://scrutinizer-ci.com/g/simPod/Kafka/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/simPod/Kafka)
[![Code Coverage](https://scrutinizer-ci.com/g/simPod/Kafka/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/simPod/Kafka)

## Installation

Add as [Composer](https://getcomposer.org/) dependency:

```sh
composer require simpod/kafka
```

## Config Constants

Some config constants are provided like `ConsumerConfig`, `ProducerConfig` or `CommonClientConfigs`. 

However, they are copied from Java API and not all are applicable to librdkafka. Consult with librdkafka documentation before use.

## Clients

### Consumer

`KafkaConsumer` boilerplate is available with `startBatch()` method ([(to suplement this example in librdkafka)](https://github.com/edenhill/librdkafka/blob/master/examples/rdkafka_consume_batch.cpp#L97)) and with `start()`. 
They also handle termination signals for you.

```php
<?php

declare(strict_types=1);

namespace Your\AppNamespace;

use RdKafka\Message;
use SimPod\Kafka\Clients\Consumer\ConsumerConfig;
use SimPod\Kafka\Clients\Consumer\ConsumerRecords;
use SimPod\Kafka\Clients\Consumer\KafkaConsumer;

final class ExampleBatchConsumer
{
    public function run() : void
    {
        $kafkaConsumer = new KafkaConsumer($this->getConfig());

        $kafkaConsumer->subscribe(['topic1']);

        $kafkaConsumer->startBatch(
            static function (Message $message) : void {
                // Process record
            },
            static function (ConsumerRecords $consumerRecords) use ($kafkaConsumer) : void {
                // Process records batch
    
                $kafkaConsumer->commit($consumerRecords->getLast());
            },
            200000, 
            120 * 1000
        );
    }

    private function getConfig() : ConsumerConfig
    {
        $config = new ConsumerConfig();

        $config->set(ConsumerConfig::BOOTSTRAP_SERVERS_CONFIG, '127.0.0.1:9092');
        $config->set(ConsumerConfig::ENABLE_AUTO_COMMIT_CONFIG, false);
        $config->set(ConsumerConfig::CLIENT_ID_CONFIG, gethostname());
        $config->set(ConsumerConfig::AUTO_OFFSET_RESET_CONFIG, 'earliest');
        $config->set(ConsumerConfig::GROUP_ID_CONFIG, 'consumer_group_name');

        return $config;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Your\AppNamespace;

use RdKafka\Message;
use SimPod\Kafka\Clients\Consumer\ConsumerConfig;
use SimPod\Kafka\Clients\Consumer\KafkaConsumer;

final class ExampleConsumer
{
    public function run() : void
    {
        $kafkaConsumer = new KafkaConsumer($this->getConfig(), Logger::get());

        $kafkaConsumer->subscribe(['topic1']);

        $kafkaConsumer->start(
            120 * 1000,
            static function (Message $message) use ($kafkaConsumer) : void {
                // Process message here

                $kafkaConsumer->commit($message); // Autocommit is disabled
            }
        );
    }

    private function getConfig() : ConsumerConfig
    {
        $config = new ConsumerConfig();

        $config->set(ConsumerConfig::BOOTSTRAP_SERVERS_CONFIG, '127.0.0.1:9092');
        $config->set(ConsumerConfig::ENABLE_AUTO_COMMIT_CONFIG, false);
        $config->set(ConsumerConfig::CLIENT_ID_CONFIG, gethostname());
        $config->set(ConsumerConfig::AUTO_OFFSET_RESET_CONFIG, 'earliest');
        $config->set(ConsumerConfig::GROUP_ID_CONFIG, 'consumer_group_name');

        return $config;
    }
}
```

### Development

There is `kwn/php-rdkafka-stubs` listed as a dev dependency so it properly integrates php-rdkafka extension with IDE.
