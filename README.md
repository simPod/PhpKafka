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

Some config constants are provided like `ConsumerConfig`, `ProducerConfig` or `CommonClientConfigs`.

However, they are copied from Java API and not all are applicable to librdkafka. Consult with librdkafka documentation before use.

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

`ConsumerRunner` composes a native `RdKafka\KafkaConsumer`. Librdkafka owns fetching, group membership,
heartbeats and reconnects. The runner owns synchronous processing, exact batch commits and rebalance drains.
It creates a fresh `RdKafka\Conf`, passes it to your initializer, then creates the native consumer.
It does not accept an existing native configuration or consumer: native configurations cannot be cloned,
and their installed callbacks cannot be read back safely.

#### Managed batch processing

```php
use RdKafka\Conf;
use SimPod\Kafka\Clients\Consumer\BatchLimits;
use SimPod\Kafka\Clients\Consumer\ConsumerBatch;
use SimPod\Kafka\Clients\Consumer\ConsumerRunner;

$runner = new ConsumerRunner(static function (Conf $config): void {
    $config->set('bootstrap.servers', '127.0.0.1:9092');
    $config->set('group.id', 'consumer_group_name');
    $config->set('auto.offset.reset', 'earliest');
    $config->set('enable.auto.commit', 'false');
    // Install any native error, statistics or other non-rebalance callbacks here.
});
$runner->subscribe(['topic1', 'topic2']);

try {
    $runner->runBatch(
        new BatchLimits(pollWaitMs: 100, maxAgeMs: 500, maxRecords: 1000),
        static function (ConsumerBatch $batch): void {
            foreach ($batch as $message) {
                // Process every record. Throw if any record fails.
            }
        },
    ); // Each successful handler is followed by a synchronous exact-offset commit.
} finally {
    $runner->close();
}
```

`ConsumerBatch` is a stable, countable iterable snapshot. Retaining it does not retain an accumulator that the
next poll clears. Iteration returns independent native message copies. `nextOffsets()` returns one native
`TopicPartition` per represented topic-partition, at its highest processed offset **plus one**. For example,
records at `topic1/0:7` and `topic1/1:20` require commits at `topic1/0:8` and `topic1/1:21`.
Committing only `getLast()` acknowledges only that last record's partition. A no-argument native `commit()`
can acknowledge fetched but unprocessed records in other partitions. Neither is a batch commit.

For explicit acknowledgment, use the same single handler with automatic commit-after-processing disabled:

```php
$runner->runBatch(
    new BatchLimits(pollWaitMs: 100, maxAgeMs: 500, maxRecords: 1000),
    static function (ConsumerBatch $batch) use ($runner): void {
        foreach ($batch as $message) {
            // Process record.
        }

        $runner->commitBatch($batch);
        // This requests an exact commit; it executes only after this handler returns successfully.
    },
    commitAfterProcessing: false,
);
```

`commitBatch()` accepts only the batch currently being handled. A retained or constructed batch cannot rewind
the runner's committed offsets. Omitting the commit request in explicit mode leaves that batch unacknowledged;
later commits on the same partition still advance past earlier offsets, so this is not a retry queue.
Do not return successfully from a handler that has left work incomplete.

The runner requires `enable.auto.commit=false`; native background commits are incompatible with this processing
contract. It leaves `enable.auto.offset.store` unchanged. With native auto-commit disabled, explicit offset-vector
commits do not depend on the native offset store. This contract is at-least-once: a crash or commit failure after
application side effects can cause replay. It does not provide exactly-once processing or an atomic commit with
an external database.

#### Limits, shutdown and errors

- All `BatchLimits` values must be positive. `pollWaitMs` is the maximum wait for one native consume call.
  It must fit the native signed 32-bit millisecond range; `maxAgeMs` must fit the nanosecond clock arithmetic.
  `maxAgeMs` starts when the first record enters an empty batch. It uses a monotonic nanosecond clock, and each
  later poll is bounded by the remaining budget. `maxRecords` bounds the number of records in a batch.
- Optional `maxBytes` bounds payload and key bytes: drain the current batch before admitting a record that would
  exceed the threshold. A single oversized record is processed alone, so this is a soft limit. It excludes headers,
  PHP object overhead and librdkafka's own buffers; it is not a process memory limit.
- `run(callable $handler, int $pollWaitMs = 1000)` processes and commits one message at a time. Both modes require
  one processing handler. Processing is synchronous; handler execution and native callbacks cannot be preempted
  by the batch-age limit.
- Applications request shutdown with `requestStop()`. A normal stop drains a nonempty partial batch, then commits
  it after successful processing. Stop is also safe before a run. A run is single-use; create a new consumer after
  it returns or throws. `close()` is explicit and idempotent, and must run after the loop has returned.
- Handler, native callback, commit and terminal consume exceptions propagate. Failed work is not committed,
  retried during shutdown, or flushed as a final successful batch. Already successful commits remain committed.
  EOF and poll timeouts are normal; transport and all-brokers-down events allow native reconnection.
- The library installs no process signal handlers, dispatches no signals, changes no signal masks and does not
  set `internal.termination.signal`. Connect your application's shutdown mechanism to `requestStop()`.
  `ext-pcntl` is optional and needed only if your application uses it.

Keep the time between native polls, including each complete handler and its synchronous commit, below
`max.poll.interval.ms`. Size batches and bound external calls accordingly. Rebalance handlers also need to
complete within the group's rebalance budget. Native background heartbeats or pausing partitions do not remove
the maximum-poll-interval requirement. A deadline bounds polling, not application execution or commit latency.

#### Rebalance ownership

The runner reserves the fresh configuration's rebalance callback and installs it after your initializer.
Other callbacks installed by the initializer are preserved. A rebalance callback installed in that initializer
will be replaced; it does not run on the managed consumer. Reuse the initializer to construct independent consumers,
not a previously created native configuration. Use the native API directly when application-owned assignment is required;
native consumers passed to other configured callbacks must not be used to poll, commit or change assignment
behind the runner.

Before releasing revoked partitions, the runner drains the entire pending batch, including still-owned partitions
under cooperative assignment. Successful processing is committed before unassignment. If processing or committing
fails, the callback releases assignment in `finally` and propagates the failure without acknowledging failed work.
If group ownership has already been lost, Kafka can reject the commit; application side effects can then replay.

The runner supports the classic group protocol. Supported eager strategies are `range` and `roundrobin`.
`cooperative-sticky` is supported only when the installed
ext-rdkafka build exposes **both** `incrementalAssign()` and `incrementalUnassign()`. These methods are conditional
and are not guaranteed by `ext-rdkafka ^6` (they were added in 6.0.4). Unsupported or mixed protocols are rejected
before native construction. Cooperative callbacks use incremental assignment; eager callbacks use `assign()`.

#### Native compatibility and migration

`KafkaConsumer` remains a native subclass with native methods and configured callbacks intact. Without a user
rebalance callback, ordinary subscriptions use librdkafka's default assignment behavior. Its `start()` method uses
the shared processing loop without adding automatic acknowledgment. Explicit single-message commits remain valid:

```php
use RdKafka\Message;
use SimPod\Kafka\Clients\Consumer\ConsumerConfig;
use SimPod\Kafka\Clients\Consumer\KafkaConsumer;

$config = new ConsumerConfig();
$config->set('bootstrap.servers', '127.0.0.1:9092');
$config->set('group.id', 'classic_consumer_group');
$config->set('enable.auto.commit', false);
$config->set('auto.offset.reset', 'earliest');
$consumer = new KafkaConsumer($config);
$consumer->subscribe(['topic1']);
try {
    $consumer->start(100, static function (Message $message) use ($consumer): void {
        // Process message, then acknowledge this message's partition.
        $consumer->commit($message);
    });
} finally {
    $consumer->close();
}
```

This does not require disabling `enable.auto.offset.store`: native auto-commit is already disabled and the explicit
message commit supplies the offset. As with all direct native commits, the application must not commit before its
work has succeeded.

Migration changes:

- Replace subscribed `startBatch()` with `ConsumerRunner::runBatch()`. An existing native client's rebalance callback
  cannot be inspected or safely replaced after construction. Legacy `startBatch()` therefore supports only a fixed
  manual `assign()` setup, requires `enable.auto.commit=false`, and requires at least one processing callback.
- Move legacy record processing into the new batch handler. In legacy manual batch mode, record callbacks now run
  when the batch drains, followed by the batch callback. An exception prevents the latter callback.
- `ConsumerRecords` retains public `add()`, `clear()`, `forEach()` and `getLast()` for compatibility, but is deprecated.
  Each legacy delivery now uses a fresh accumulator. Use `$consumer->commitBatch($records)` for an exact native
  commit in legacy manual mode, or `$records->toBatch()` to make a stable snapshot. Legacy direct commits are
  immediate and application-owned; the runner's deferred-commit guarantee does not apply to native calls.
- `stop()` and `shutdown()` remain aliases for `requestStop()`. Runs no longer silently restart after a stop or error.
  Replace implicit signal termination with application-owned shutdown, and call `close()` in `finally`.
- Poll waits and batch ages must be positive. Terminal statuses now throw instead of being logged and ignored;
  transient broker connection notifications still permit recovery.
- The legacy `BatchTime` wall-clock utility remains available but is deprecated; the runner does not use it.

See [ADR 0003](docs/adr/0003-consumer-processing-contract.md) for the processing and ownership decision.

[GA Image]: https://github.com/simPod/PhpKafka/workflows/CI/badge.svg

[GA Link]: https://github.com/simPod/PhpKafka/actions?query=workflow%3A%22CI%22+branch%3Amaster

[Coverage Image]: https://codecov.io/gh/simPod/PhpKafka/branch/master/graph/badge.svg

[CodeCov Link]: https://codecov.io/gh/simPod/PhpKafka/branch/master

[Downloads Image]: https://poser.pugx.org/simpod/kafka/d/total.svg

[Packagist Image]: https://poser.pugx.org/simpod/kafka/v/stable.svg

[Packagist Link]: https://packagist.org/packages/simpod/kafka

[Infection Image]: https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FsimPod%2FPhpKafka%2Fmaster

[Infection Link]: https://infection.github.io
