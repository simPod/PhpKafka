# PHP Kafka boilerplate wrapper around RdKafka

[![GitHub Actions][GA Image]][GA Link]
[![Code Coverage][Coverage Image]][CodeCov Link]
[![Downloads][Downloads Image]][Packagist Link]
[![Packagist][Packagist Image]][Packagist Link]
[![Infection MSI][Infection Image]][Infection Link]

## Installation

Add as [Composer](https://getcomposer.org/) dependency:

```sh
composer require simpod/kafka
```

## Config Constants

`ConsumerConfig`, `ProducerConfig`, and `CommonClientConfigs` provide configuration key constants for
librdkafka. Pass native property names and values to `set()`; the native library validates them.
Raw keys and `getConf()` remain available for properties and callbacks that have no constant.

Legacy Java-only constants keep their original names and values for compatibility, but are marked
`@deprecated` with a native alternative or an explanation when no equivalent exists. They are not
translated automatically. For example, use `ConsumerConfig::FETCH_WAIT_MAX_MS_CONFIG` (`fetch.wait.max.ms`)
instead of the unsupported `FETCH_MAX_WAIT_MS_CONFIG` (`fetch.max.wait.ms`).

See [Configuration and migration](docs/configuration.md) for native defaults, supported aliases,
Java migration mappings, and mixed-language partitioning. This guidance uses
[librdkafka v2.6.1](https://github.com/confluentinc/librdkafka/blob/v2.6.1/CONFIGURATION.md), the CI version;
available settings depend on the librdkafka version linked to your `ext-rdkafka` installation.

## Clients

### Producer

#### Idempotence and delivery results

These mechanisms answer different questions:

| Mechanism | What it provides |
| --- | --- |
| `enable.idempotence=true` | Prevents duplicates when the native producer retries the same queued record. |
| Native `RdKafka\Producer::flush()` | Waits for local queue completion, including records that failed. |
| `KafkaProducerWrapper::flushMessages()` | Drains the queue and reports failed delivery through `DeliveryFailed`. |

Idempotence does not make a rejected or expired record successful. Librdkafka provides the final outcome through
delivery reports, which code must inspect. The wrapper registers its delivery observer automatically; you do not
need to supply a custom callback to enable this check. A user callback remains optional and is preserved.

Success has the guarantees of your configured `acks` setting; `acks=0` does not provide broker acknowledgment.
The wrapper does not override acknowledgments, idempotence, or delivery-report settings.

#### Confirm before committing source state

Call `flushMessages()` before deleting an outbox row or acknowledging an input record whose output depends on
successful publication:

```php
use SimPod\Kafka\Clients\Producer\KafkaProducerWrapper;
use SimPod\Kafka\Clients\Producer\ProducerConfig;

$config = new ProducerConfig();
$config->set(ProducerConfig::BOOTSTRAP_SERVERS_CONFIG, '127.0.0.1:9092');
$config->set(ProducerConfig::ENABLE_IDEMPOTENCE_CONFIG, true);
$producer = new KafkaProducerWrapper($config);

$producer->produce('events', null, '{"type":"example"}', key: 'event-123');
$producer->flushMessages(); // Throws DeliveryFailed when publication was not confirmed.

// Only now commit the outbox deletion or acknowledge the input record.
```

If confirmation throws, retain the source record for recovery. A record that failed permanently must not be
treated as sent simply because the native queue became empty.

#### Retrying an outbox after a database failure

An application retry is a new `produce()` call. It receives a new producer sequence number, even when it uses the
same producer, key, and payload:

```text
produce(A)       -> Kafka accepts record A, sequence 10
PG commit fails -> the outbox row remains
produce(A) again -> Kafka accepts another record A, sequence 11
```

Idempotence deduplicates a native retransmission of sequence 10; it does not deduplicate the new record with
sequence 11. See Kafka's warning about
[application-level re-sends](https://kafka.apache.org/41/javadoc/org/apache/kafka/clients/producer/KafkaProducer.html).

Retrying this outbox is a valid at-least-once design when consumers tolerate duplicate deliveries or deduplicate
logical message IDs. Atomic PostgreSQL and Kafka commits are not required for that design. Delivery confirmation
prevents known delivery failure from being committed as success; it does not remove the replay window or confirm
downstream execution.

#### Failure state, callbacks, and shutdown

The first unsuccessful delivery report remains recorded. A later successful report or empty flush does not clear
it. `produce()` can also surface a failed report from an earlier record. Recovery from such a terminal record
failure uses a new wrapper/native producer. A native flush timeout is reported separately and can be retried while
the native producer still has pending work.

Callbacks configured through `$config->getConf()->setDrMsgCb(...)` continue to receive the original native producer
and message. Each native producer retains the callback configured when it was created, even when its configuration
is reused. Callback exceptions propagate unchanged. Delivery tracking also covers records submitted through the
public `getProducer()` native API.

An exit callback remains application code. Catch and log previously unobserved shutdown delivery failures there;
do not use destruction as an acknowledgment boundary. The wrapper suppresses only its duplicate report-error check
when a previously surfaced delivery failure is flushed again during destruction.

### Consumer

`KafkaConsumer` boilerplate is available with `startBatch()` method ([to suplement this example in librdkafka](https://github.com/edenhill/librdkafka/blob/master/examples/rdkafka_consume_batch.cpp#L97)) and with `start()`. They also handle
termination signals for you.

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
    public function run(): void
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

    private function getConfig(): ConsumerConfig
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
    public function run(): void
    {
        $kafkaConsumer = new KafkaConsumer($this->getConfig());

        $kafkaConsumer->subscribe(['topic1']);

        $kafkaConsumer->startBatch(
            200000, 
            120 * 1000,
            static function (Message $message): void {
                // Process record
            },
            static function (ConsumerRecords $consumerRecords) use ($kafkaConsumer) : void {
                // Process records batch
    
                $kafkaConsumer->commit($consumerRecords->getLast());
            }
        );
    }

    private function getConfig(): ConsumerConfig
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

[GA Image]: https://github.com/simPod/PhpKafka/workflows/CI/badge.svg

[GA Link]: https://github.com/simPod/PhpKafka/actions?query=workflow%3A%22CI%22+branch%3Amaster

[Coverage Image]: https://codecov.io/gh/simPod/PhpKafka/branch/master/graph/badge.svg

[CodeCov Link]: https://codecov.io/gh/simPod/PhpKafka/branch/master

[Downloads Image]: https://poser.pugx.org/simpod/kafka/d/total.svg

[Packagist Image]: https://poser.pugx.org/simpod/kafka/v/stable.svg

[Packagist Link]: https://packagist.org/packages/simpod/kafka

[Infection Image]: https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FsimPod%2FPhpKafka%2Fmaster

[Infection Link]: https://infection.github.io
