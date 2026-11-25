# Configuration and migration

## Native configuration contract

`ConsumerConfig`, `ProducerConfig`, and `CommonClientConfigs` use native librdkafka properties.
`set()` converts booleans to `true`/`false` strings and integers to decimal strings, then calls
`RdKafka\Conf::set()`. The native library validates the key and value and throws `RdKafka\Exception`
when it rejects them. Some cross-property constraints are checked when the native client is created.
This library does not translate Java property names or install its own configuration defaults.

You can use a raw native key even when this library has no constant:

```php
use SimPod\Kafka\Clients\Consumer\ConsumerConfig;

$config = new ConsumerConfig();
$config->set(ConsumerConfig::BOOTSTRAP_SERVERS_CONFIG, '127.0.0.1:9092');
$config->set(ConsumerConfig::GROUP_ID_CONFIG, 'events');
$config->set(ConsumerConfig::AUTO_OFFSET_RESET_CONFIG, 'error');
$config->set(ConsumerConfig::FETCH_WAIT_MAX_MS_CONFIG, 100);
$config->set('fetch.error.backoff.ms', 250);
```

`getConf()` gives access to the native configuration and callback APIs. Producer configuration still
returns `ProducerConf`, including its delivery-report callback support. Use `setDrMsgCb()` there as
before; native configuration values do not replace callback registration.

`get()` reads the native global configuration dump. The dump uses canonical property names and
omits aliases. For example, after setting `bootstrap.servers`, read `metadata.broker.list`; after
setting `linger.ms`, read `queue.buffering.max.ms`. Topic properties such as `partitioner`,
`acks`, `delivery.timeout.ms`, and `auto.offset.reset` are accepted by `RdKafka\Conf::set()` as
default topic settings, but are not included in its global dump. `get()` does not resolve aliases
or read those topic settings. Use `RdKafka\TopicConf::dump()` to inspect a separately configured
topic configuration. Do not treat the dump as a complete configuration round-trip.

With librdkafka v2.6.1, `$config->getConf()->dump()` includes the global `group.protocol`
property, whose default is `classic`. Read it with
`$config->getConf()->dump()['group.protocol'] ?? null` when the linked native version is unknown;
older versions can omit this property. The value selects a group protocol (`classic` or `consumer`),
not a librdkafka version, and does not report the protocol negotiated by a running consumer.
Its presence does not establish that the PHP extension or this wrapper supports every native
protocol API. Use `php --ri rdkafka` to identify the linked library version.

## Version and build differences

This audit uses [librdkafka v2.6.1 CONFIGURATION.md][native-config], the version used in CI,
and the [ext-rdkafka 6 configuration implementation][extension-conf]. The package requires
`ext-rdkafka ^6`; that constraint does **not** pin librdkafka to v2.6.1. The extension's
[build configuration][extension-build] accepts librdkafka versions from 0.11.0 and checks optional
APIs at build time. A property, enum value, or API in the v2.6.1 reference can be unavailable with
an older native library. Later native versions can also change defaults and supported properties.
Check the linked version with `php --ri rdkafka` and use its matching native documentation.

TLS, SASL mechanisms, and compression codecs also depend on how librdkafka was built. A constant
does not guarantee a build feature or broker capability. Native validation and client creation
remain the source of truth for the installed library.

These defaults and meanings are from v2.6.1, not from Java client documentation:

| Setting | Native default or meaning |
| --- | --- |
| `auto.offset.reset` | `largest` (alias: `latest`). Use `error`, not Java's `none`, to report a missing or out-of-range offset through `message->err` as `ERR__AUTO_OFFSET_RESET`. |
| `fetch.wait.max.ms` | 500 ms. This is the native name, not `fetch.max.wait.ms`. |
| `isolation.level` | `read_committed`. `read_uncommitted` also exposes aborted transactional messages. |
| `partition.assignment.strategy` | `range,roundrobin`. Use names such as `range`, `roundrobin`, or `cooperative-sticky`, not Java class names. Do not mix cooperative and eager strategies. |
| `linger.ms` | 5 ms; alias for `queue.buffering.max.ms`. |
| `partitioner` | `consistent_random`, which uses CRC32 for non-empty keys. |
| `batch.size` | 1000000 bytes including protocol framing, with a minimum of 1. The limit is applied after the first message, so it does not exclude an oversized first message. |
| `message.max.bytes` | 1000000 bytes. Native protocol framing can allow a ProduceRequest to exceed this by one message; the broker enforces its own topic limit. |
| `enable.auto.commit` / `enable.auto.offset.store` | Both `true`. Committing writes offsets to the broker; storing tracks the next offset to commit in memory. |
| `request.timeout.ms` | 30000 ms. Producer acknowledgment timeout enforced by the broker when `acks != 0`; not a consumer network timeout. |
| `socket.timeout.ms` | 60000 ms. Consumer fetch requests use `fetch.wait.max.ms + socket.timeout.ms`. |
| `delivery.timeout.ms` | 300000 ms; alias for `message.timeout.ms`. Includes native retries; 0 means infinite. A transactional ID can adjust this default. |
| `socket.send.buffer.bytes` / `socket.receive.buffer.bytes` | 0 selects the OS default, not Java's -1. |
| `reconnect.backoff.ms` | 100 ms, with -25% to +50% jitter and exponential growth up to `reconnect.backoff.max.ms` (10000 ms). |

The table describes native settings. This library's `KafkaConsumer` installs a rebalance callback
that uses `assign()`, not incremental assignment. With that wrapper, use `range` or `roundrobin`,
and keep `group.protocol=classic` when the linked native version exposes that property. Native
support for `cooperative-sticky` or `group.protocol=consumer` does not make the wrapper's
rebalance handler compatible with those protocols.

When processing takes a long time, consider `enable.auto.offset.store=false`. For simple explicit
commits, also set `enable.auto.commit=false` and call `$consumer->commit($message)` only after
successful processing. `RdKafka\KafkaConsumer::commit($message)` commits the message's offset
plus one; do not increment it yourself. This PHP method is also inherited by this library's
`KafkaConsumer`.

If you retain automatic commits, store only successfully processed offsets with
`$consumer->newTopic($message->topic_name)->offsetStore($message->partition, $message->offset)`.
Pass the processed message's offset unchanged: this PHP topic API calls the native single-offset
store, which adds one. `ext-rdkafka ^6` does not expose `offsetsStore()` on its high-level
`RdKafka\KafkaConsumer`. See the [PHP consumer API][extension-consumer],
[PHP topic API][extension-topic], and [native offset-store implementation][native-offset-store].

Disabling automatic storage and disabling automatic commit are separate decisions. Neither setting
removes the need to call the PHP consumer's `consume($timeoutMs)` within `max.poll.interval.ms`.

## Legacy Java keys

The following constants remain available with their original values. Their `@deprecated` comments
identify unsupported native keys or Java-only defaults. They still reach native validation unchanged.
The mappings below are migration guidance, not aliases or automatic unit conversions.

| Legacy key / constant | Native migration |
| --- | --- |
| `fetch.max.wait.ms` / `ConsumerConfig::FETCH_MAX_WAIT_MS_CONFIG` | Use `fetch.wait.max.ms` / `FETCH_WAIT_MAX_MS_CONFIG`. |
| `send.buffer.bytes` / `SEND_BUFFER_CONFIG` | Use `socket.send.buffer.bytes` / `SOCKET_SEND_BUFFER_BYTES_CONFIG`. Use 0 for the OS default. |
| `receive.buffer.bytes` / `RECEIVE_BUFFER_CONFIG` | Use `socket.receive.buffer.bytes` / `SOCKET_RECEIVE_BUFFER_BYTES_CONFIG`. Use 0 for the OS default. |
| `SEND_BUFFER_LOWER_BOUND`, `RECEIVE_BUFFER_LOWER_BOUND` | These retain Java's -1 value. The native socket buffer properties have a lower bound of 0. |
| `max.request.size` / `ProducerConfig::MAX_REQUEST_SIZE_CONFIG` | Use `message.max.bytes` / `MESSAGE_MAX_BYTES_CONFIG`; native framing and enforcement differ. |
| `buffer.memory` / `ProducerConfig::BUFFER_MEMORY_CONFIG` | Use `queue.buffering.max.kbytes` / `QUEUE_BUFFERING_MAX_KBYTES_CONFIG` in kilobytes, not bytes. It limits queued message sizes, not all producer memory. `queue.buffering.max.messages` also limits the count. |
| `partitioner.class` / `ProducerConfig::PARTITIONER_CLASS_CONFIG` | Use `partitioner` / `PARTITIONER_CONFIG` with a native name. There is no Java class loader. |
| `max.block.ms` / `ProducerConfig::MAX_BLOCK_MS_CONFIG` | No native global blocking-timeout equivalent. Handle queue-full errors and retry policy in PHP; APIs such as `flush()` accept their own timeout. |
| `max.poll.records` / `ConsumerConfig::MAX_POLL_RECORDS_CONFIG` | No native equivalent. Limit records in the application batch loop. `queued.min.messages` and `queued.max.messages.kbytes` control prefetch queues, not batch return counts. |
| `default.api.timeout.ms` / `ConsumerConfig::DEFAULT_API_TIMEOUT_MS_CONFIG` | No native global API timeout equivalent. Pass timeouts to individual APIs that accept them. |
| `key.serializer`, `value.serializer` / producer `*_SERIALIZER_CLASS_CONFIG` | No native serializer-class equivalent. Serialize keys and payloads to bytes in PHP before producing. |
| `key.deserializer`, `value.deserializer` / consumer `*_DESERIALIZER_CLASS_CONFIG` | No native deserializer-class equivalent. Deserialize received keys and payloads in PHP. |
| `interceptor.classes` / `INTERCEPTOR_CLASSES_CONFIG` | No PHP interceptor-class equivalent. Use application code. Native C interceptors are not configured as PHP or Java classes. |
| `metric.reporters` / `METRIC_REPORTER_CLASSES_CONFIG` | No reporter-class equivalent. Use `statistics.interval.ms` / `STATISTICS_INTERVAL_MS_CONFIG` and `getConf()->setStatsCb()` for native statistics. |
| `metrics.num.samples` / `METRICS_NUM_SAMPLES_CONFIG` | No native sampling-count equivalent. |
| `metrics.recording.level` / `METRICS_RECORDING_LEVEL_CONFIG` | No native metrics-recording-level equivalent. |
| `metrics.sample.window.ms` / `METRICS_SAMPLE_WINDOW_MS_CONFIG` | No native sampling-window equivalent. `statistics.interval.ms` controls emission, not sampling. |
| `exclude.internal.topics` / `ConsumerConfig::EXCLUDE_INTERNAL_TOPICS_CONFIG` and `DEFAULT_EXCLUDE_INTERNAL_TOPICS` | No exact native equivalent. `topic.blacklist` can filter topic names, but does not implement this Java switch. |
| `internal.leave.group.on.close` / `ConsumerConfig::LEAVE_GROUP_ON_CLOSE_CONFIG` | No native leave-on-close switch. `group.instance.id` enables static membership with different semantics and requires broker >= 2.3.0. |

Shared deprecated keys are marked in `CommonClientConfigs` and in both client classes that expose
them. Public documentation constants for unsupported options are also deprecated. Native
`request.timeout.ms` remains available, including the historical consumer constant, but it affects
producer acknowledgments only. For consumer network timeouts, use `socket.timeout.ms`.

## Supported native aliases

These Java-looking names are genuine native aliases and are **not** deprecated:

| Public key | Canonical native key |
| --- | --- |
| `bootstrap.servers` | `metadata.broker.list` |
| `linger.ms` | `queue.buffering.max.ms` |
| `retries` | `message.send.max.retries` |
| `compression.type` | `compression.codec` |
| `max.partition.fetch.bytes` | `fetch.message.max.bytes` |
| `delivery.timeout.ms` | `message.timeout.ms` (topic configuration) |
| `acks` | `request.required.acks` (topic configuration) |

The existing `SASL_MECHANISM` constant contains the canonical native key `sasl.mechanisms`.
Despite the plural name, configure only one mechanism.

## Mixed-language producer partitioning

To match the Java producer's Murmur2 keyed partition mapping, configure:

```php
use SimPod\Kafka\Clients\Producer\ProducerConfig;

$config = new ProducerConfig();
$config->set(ProducerConfig::PARTITIONER_CONFIG, 'murmur2_random');
```

The native default `consistent_random` hashes non-empty keys with CRC32 and can select different
partitions than Java. Native `murmur2_random` uses Java-compatible Murmur2 hashing and randomly
partitions null keys. Use identical serialized key bytes and the same partition count in all
languages. An explicit partition bypasses this key-based mapping.

Keep **null** and **empty string** keys distinct. With `murmur2_random`, null keys are unkeyed
and distributed randomly (native sticky batching can affect that distribution); empty strings are
hashed as zero-length keys. With `consistent_random`, both null and empty keys are distributed
randomly. The native `murmur2` variant hashes null keys to a single partition instead of using
the null-key behavior of `murmur2_random`.

This mapping does not guarantee identical partition sequences for unkeyed messages across Java
versions and native versions. Batching and sticky partition selection can differ. Do not convert
null keys to empty strings when crossing a language boundary.

[native-config]: https://github.com/confluentinc/librdkafka/blob/v2.6.1/CONFIGURATION.md
[extension-conf]: https://github.com/arnaud-lb/php-rdkafka/blob/6.0.5/conf.c
[extension-build]: https://github.com/arnaud-lb/php-rdkafka/blob/6.0.5/config.m4
[extension-consumer]: https://github.com/arnaud-lb/php-rdkafka/blob/6.0.5/kafka_consumer.c
[extension-topic]: https://github.com/arnaud-lb/php-rdkafka/blob/6.0.5/topic.stub.php
[native-offset-store]: https://github.com/confluentinc/librdkafka/blob/v2.6.1/src/rdkafka_offset.c
