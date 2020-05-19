# PHP Kafka boilerplate wrapper around RdKafka

[![Build Status](https://github.com/simPod/PhpKafka/workflows/CI/badge.svg?branch=master)](https://github.com/simPod/PhpKafka/actions)
[![Coverage Status](https://coveralls.io/repos/github/simPod/PhpKafka/badge.svg?branch=master)](https://coveralls.io/github/simPod/PhpKafka?branch=master)
[![Downloads](https://poser.pugx.org/simpod/kafka/d/total.svg)](https://packagist.org/packages/simpod/kafka)
[![Packagist](https://poser.pugx.org/simpod/kafka/v/stable.svg)](https://packagist.org/packages/simpod/kafka)
[![Licence](https://poser.pugx.org/simpod/kafka/license.svg)](https://packagist.org/packages/simpod/kafka)
[![GitHub Issues](https://img.shields.io/github/issues/simPod/PhpKafka.svg?style=flat-square)](https://github.com/simPod/PhpKafka/issues)
[![Type Coverage](https://shepherd.dev/github/simPod/PhpKafka/coverage.svg)](https://shepherd.dev/github/simPod/PhpKafka)
[![Infection MSI](https://badge.stryker-mutator.io/github.com/simPod/PhpKafka/master)](https://infection.github.io)

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

`KafkaConsumer` boilerplate is available with `startBatch()` method ([to suplement this example in librdkafka](https://github.com/edenhill/librdkafka/blob/master/examples/rdkafka_consume_batch.cpp#L97)) and with `start()`. 
They also handle termination signals for you.

#### Classic Consumer

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

#### Batching Consumer

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
            200000, 
            120 * 1000,
            static function (Message $message) : void {
                // Process record
            },
            static function (ConsumerRecords $consumerRecords) use ($kafkaConsumer) : void {
                // Process records batch
    
                $kafkaConsumer->commit($consumerRecords->getLast());
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
