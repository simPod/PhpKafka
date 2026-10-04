<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Consumer;

use SimPod\Kafka\Clients\CommonClientConfigs;
use SimPod\Kafka\Common\Config;

//phpcs:disable Cdn77.NamingConventions.ValidConstantName.ClassConstantNotUpperCase
//phpcs:disable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedConstant
//phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
//phpcs:disable SlevomatCodingStandard.TypeHints.ClassConstantTypeHint.MissingNativeTypeHint

/**
 * Native librdkafka consumer keys, including legacy Java keys retained for compatibility.
 * See docs/configuration.md for migration guidance and version-specific defaults.
 */
final class ConsumerConfig extends Config
{
    /**
     * <code>group.id</code>
     */
    public const string  GROUP_ID_CONFIG = 'group.id';

    /** @deprecated Unsupported Java key. No native equivalent; limit records in the application batch loop. */
    public const string  MAX_POLL_RECORDS_CONFIG = 'max.poll.records';

    /** <code>max.poll.interval.ms</code> */
    public const string  MAX_POLL_INTERVAL_MS_CONFIG = 'max.poll.interval.ms';

    /**
     * <code>session.timeout.ms</code>
     */
    public const string  SESSION_TIMEOUT_MS_CONFIG = 'session.timeout.ms';

    /**
     * <code>heartbeat.interval.ms</code>
     */
    public const string  HEARTBEAT_INTERVAL_MS_CONFIG = 'heartbeat.interval.ms';

    /**
     * <code>bootstrap.servers</code>
     */
    public const BOOTSTRAP_SERVERS_CONFIG = CommonClientConfigs::BOOTSTRAP_SERVERS_CONFIG;

    /** <code>client.dns.lookup</code> */
    public const CLIENT_DNS_LOOKUP_CONFIG = CommonClientConfigs::CLIENT_DNS_LOOKUP_CONFIG;

    /**
     * <code>enable.auto.commit</code>
     */
    public const string  ENABLE_AUTO_COMMIT_CONFIG = 'enable.auto.commit';

    /** Automatically store the next offset to commit when a message is delivered to the application. Default: true. */
    public const string ENABLE_AUTO_OFFSET_STORE_CONFIG = 'enable.auto.offset.store';

    /**
     * <code>auto.commit.interval.ms</code>
     */
    public const string  AUTO_COMMIT_INTERVAL_MS_CONFIG = 'auto.commit.interval.ms';

    /**
     * Native strategy names: range, roundrobin, cooperative-sticky. Default: range,roundrobin.
     * Do not mix cooperative and eager strategies.
     */
    public const string  PARTITION_ASSIGNMENT_STRATEGY_CONFIG = 'partition.assignment.strategy';

    /**
     * <code>auto.offset.reset</code>
     */
    public const string AUTO_OFFSET_RESET_CONFIG = 'auto.offset.reset';
    public const string AUTO_OFFSET_RESET_DOC = 'Action when there is no stored offset or the requested offset is out of range: <code>earliest</code> (aliases: smallest, beginning), <code>latest</code> (aliases: largest, end; native default), or <code>error</code> (deliver ERR__AUTO_OFFSET_RESET through the consumed message error). The Java value <code>none</code> is unsupported.';

    /**
     * <code>fetch.min.bytes</code>
     */
    public const string  FETCH_MIN_BYTES_CONFIG = 'fetch.min.bytes';

    /**
     * <code>fetch.max.bytes</code>
     */
    public const string  FETCH_MAX_BYTES_CONFIG = 'fetch.max.bytes';

    public const int  DEFAULT_FETCH_MAX_BYTES = 50 * 1024 * 1024;

    /**
     * @deprecated Unsupported Java key. Use fetch.wait.max.ms (FETCH_WAIT_MAX_MS_CONFIG).
     */
    public const string  FETCH_MAX_WAIT_MS_CONFIG = 'fetch.max.wait.ms';

    /** Maximum broker wait in milliseconds to fill a fetch response with fetch.min.bytes. Default: 500. */
    public const string FETCH_WAIT_MAX_MS_CONFIG = 'fetch.wait.max.ms';

    /** Minimum messages per topic-partition librdkafka tries to keep in its local consumer queue. */
    public const string QUEUED_MIN_MESSAGES_CONFIG = 'queued.min.messages';

    /** Local prefetched queue limit in kilobytes; takes priority over queued.min.messages and can be overshot by a fetch. */
    public const string QUEUED_MAX_MESSAGES_KBYTES_CONFIG = 'queued.max.messages.kbytes';

    /** <code>metadata.max.age.ms</code> */
    public const METADATA_MAX_AGE_CONFIG = CommonClientConfigs::METADATA_MAX_AGE_CONFIG;

    /**
     * Native alias for fetch.message.max.bytes: initial per-partition fetch size, increased for larger messages.
     */
    public const string  MAX_PARTITION_FETCH_BYTES_CONFIG = 'max.partition.fetch.bytes';

    public const int  DEFAULT_MAX_PARTITION_FETCH_BYTES = 1 * 1024 * 1024;

    /** @deprecated Unsupported Java key. Use socket.send.buffer.bytes (0 selects the OS default). */
    public const SEND_BUFFER_CONFIG = CommonClientConfigs::SEND_BUFFER_CONFIG;

    /** @deprecated Unsupported Java key. Use socket.receive.buffer.bytes (0 selects the OS default). */
    public const RECEIVE_BUFFER_CONFIG = CommonClientConfigs::RECEIVE_BUFFER_CONFIG;

    public const SOCKET_SEND_BUFFER_BYTES_CONFIG = CommonClientConfigs::SOCKET_SEND_BUFFER_BYTES_CONFIG;
    public const SOCKET_RECEIVE_BUFFER_BYTES_CONFIG = CommonClientConfigs::SOCKET_RECEIVE_BUFFER_BYTES_CONFIG;
    public const SOCKET_TIMEOUT_MS_CONFIG = CommonClientConfigs::SOCKET_TIMEOUT_MS_CONFIG;
    public const MESSAGE_MAX_BYTES_CONFIG = CommonClientConfigs::MESSAGE_MAX_BYTES_CONFIG;
    public const STATISTICS_INTERVAL_MS_CONFIG = CommonClientConfigs::STATISTICS_INTERVAL_MS_CONFIG;

    /**
     * <code>client.id</code>
     */
    public const CLIENT_ID_CONFIG = CommonClientConfigs::CLIENT_ID_CONFIG;

    /**
     * <code>reconnect.backoff.ms</code>
     */
    public const RECONNECT_BACKOFF_MS_CONFIG = CommonClientConfigs::RECONNECT_BACKOFF_MS_CONFIG;

    /**
     * <code>reconnect.backoff.max.ms</code>
     */
    public const RECONNECT_BACKOFF_MAX_MS_CONFIG = CommonClientConfigs::RECONNECT_BACKOFF_MAX_MS_CONFIG;

    /**
     * <code>retry.backoff.ms</code>
     */
    public const RETRY_BACKOFF_MS_CONFIG = CommonClientConfigs::RETRY_BACKOFF_MS_CONFIG;

    /**
     * @deprecated Unsupported Java key. No native sampling-window equivalent; statistics.interval.ms controls emission only.
     */
    public const METRICS_SAMPLE_WINDOW_MS_CONFIG = CommonClientConfigs::METRICS_SAMPLE_WINDOW_MS_CONFIG;

    /**
     * @deprecated Unsupported Java key. No native sampling-count equivalent.
     */
    public const METRICS_NUM_SAMPLES_CONFIG = CommonClientConfigs::METRICS_NUM_SAMPLES_CONFIG;

    /**
     * @deprecated Unsupported Java key. No native metrics-recording-level equivalent.
     */
    public const METRICS_RECORDING_LEVEL_CONFIG = CommonClientConfigs::METRICS_RECORDING_LEVEL_CONFIG;

    /**
     * @deprecated Unsupported Java key. Use statistics.interval.ms and RdKafka\Conf::setStatsCb(); no reporter-class equivalent.
     */
    public const METRIC_REPORTER_CLASSES_CONFIG = CommonClientConfigs::METRIC_REPORTER_CLASSES_CONFIG;

    /**
     * <code>check.crcs</code>
     */
    public const string  CHECK_CRCS_CONFIG = 'check.crcs';

    /** @deprecated Unsupported Java key. No native deserializer equivalent; deserialize the key in PHP. */
    public const string KEY_DESERIALIZER_CLASS_CONFIG = 'key.deserializer';
    /** @deprecated No native deserializer equivalent; deserialize the key in PHP. */
    public const string KEY_DESERIALIZER_CLASS_DOC = 'Java deserializer classes are unsupported. Deserialize the message key in application code.';

    /** @deprecated Unsupported Java key. No native deserializer equivalent; deserialize the payload in PHP. */
    public const string VALUE_DESERIALIZER_CLASS_CONFIG = 'value.deserializer';
    /** @deprecated No native deserializer equivalent; deserialize the payload in PHP. */
    public const string VALUE_DESERIALIZER_CLASS_DOC = 'Java deserializer classes are unsupported. Deserialize the message payload in application code.';

    /** <code>connections.max.idle.ms</code> */
    public const CONNECTIONS_MAX_IDLE_MS_CONFIG = CommonClientConfigs::CONNECTIONS_MAX_IDLE_MS_CONFIG;

    /** Producer-only broker acknowledgment timeout. For consumer network timeouts use socket.timeout.ms. */
    public const  REQUEST_TIMEOUT_MS_CONFIG = CommonClientConfigs::REQUEST_TIMEOUT_MS_CONFIG;

    /** @deprecated Unsupported Java key. No native global API timeout equivalent; pass timeouts to individual APIs. */
    public const string DEFAULT_API_TIMEOUT_MS_CONFIG = 'default.api.timeout.ms';
    /** @deprecated No native global API timeout equivalent; pass timeouts to individual APIs. */
    public const string DEFAULT_API_TIMEOUT_MS_DOC = 'Java default API timeouts are unsupported. Pass a timeout to native APIs that accept one.';

    /** @deprecated Unsupported Java key. No PHP interceptor-class equivalent; handle messages in application code. */
    public const string INTERCEPTOR_CLASSES_CONFIG = 'interceptor.classes';
    /** @deprecated No PHP interceptor-class equivalent; handle messages in application code. */
    public const string INTERCEPTOR_CLASSES_DOC = 'Java interceptor classes are unsupported. Native C interceptors are not a PHP class configuration option.';

    /** @deprecated Unsupported Java key. No exact native equivalent; topic.blacklist can filter topic names. */
    public const string  EXCLUDE_INTERNAL_TOPICS_CONFIG = 'exclude.internal.topics';

    /** @deprecated Java default for an unsupported key; no native equivalent. */
    public const true  DEFAULT_EXCLUDE_INTERNAL_TOPICS = true;

    /**
     * @deprecated Unsupported Java key. No native leave-on-close switch; group.instance.id supports static membership.
     */
    public const string LEAVE_GROUP_ON_CLOSE_CONFIG = 'internal.leave.group.on.close';

    /** <code>isolation.level</code> */
    public const string ISOLATION_LEVEL_CONFIG = 'isolation.level';
    public const string ISOLATION_LEVEL_DOC = 'Controls transactional message visibility: <code>read_committed</code> (native default) returns only committed transactional messages; <code>read_uncommitted</code> also returns aborted transactional messages. Non-transactional messages are returned in both modes. Open transactions can delay visibility in read_committed mode.';
    private const string GROUP_ID_DOC = 'Consumer group identifier. Clients with the same group.id belong to the same group. Required for the high-level KafkaConsumer.';
    private const string MAX_POLL_RECORDS_DOC = 'Unsupported Java key; limit the number of records in the application batch loop.';
    private const string MAX_POLL_INTERVAL_MS_DOC = 'Maximum time between calls to consume messages with the high-level consumer. Exceeding this interval causes group rebalancing and can prevent offset commits. For long processing, consider <code>enable.auto.offset.store=false</code> and store offsets after processing.';
    private const string SESSION_TIMEOUT_MS_DOC = 'The timeout used to detect consumer failures when using ' .
    "Kafka's group management facility. The consumer sends periodic heartbeats to indicate its liveness " .
    'to the broker. If no heartbeats are received by the broker before the expiration of this session timeout, ' .
    'then the broker will remove this consumer from the group and initiate a rebalance. Note that the value ' .
    'must be in the allowable range as configured in the broker configuration by <code>group.min.session.timeout.ms</code> ' .
    'and <code>group.max.session.timeout.ms</code>.';
    private const string HEARTBEAT_INTERVAL_MS_DOC = 'The expected time between heartbeats to the consumer ' .
    "coordinator when using Kafka's group management facilities. Heartbeats are used to ensure that the " .
    "consumer's session stays active and to facilitate rebalancing when new consumers join or leave the group. " .
    'The value must be set lower than <code>session.timeout.ms</code>, but typically should be set no higher ' .
    'than 1/3 of that value. It can be adjusted even lower to control the expected time for normal rebalances.';
    private const string ENABLE_AUTO_COMMIT_DOC = "If true the consumer's offset will be periodically committed in the background.";
    private const string AUTO_COMMIT_INTERVAL_MS_DOC = 'The frequency in milliseconds that the consumer offsets are auto-committed to Kafka if <code>enable.auto.commit</code> is set to <code>true</code>.';
    private const string PARTITION_ASSIGNMENT_STRATEGY_DOC = 'Comma-separated native strategy names: range, roundrobin, cooperative-sticky. Default: range,roundrobin. Cooperative and eager strategies must not be mixed; Java class names are unsupported.';
    private const string FETCH_MIN_BYTES_DOC = 'Minimum bytes in a broker fetch response. When <code>fetch.wait.max.ms</code> expires, the broker returns available data regardless of this setting. Default: 1 byte.';
    private const string FETCH_MAX_BYTES_DOC = 'The maximum amount of data the server should return for a fetch request. ' .
    'Records are fetched in batches by the consumer, and if the first record batch in the first non-empty partition of the fetch is larger than ' .
    'this value, the record batch will still be returned to ensure that the consumer can make progress. As such, this is not a absolute maximum. ' .
    'The maximum record batch size accepted by the broker is defined via <code>message.max.bytes</code> (broker config) or ' .
    '<code>max.message.bytes</code> (topic config). Note that the consumer performs multiple fetches in parallel.';
    private const string FETCH_MAX_WAIT_MS_DOC = 'Unsupported Java key. Use <code>fetch.wait.max.ms</code> for the maximum broker wait to fill a fetch response with fetch.min.bytes.';
    private const string MAX_PARTITION_FETCH_BYTES_DOC = 'Alias for <code>fetch.message.max.bytes</code>: initial maximum fetch bytes per topic-partition. Librdkafka increases this value when needed to fetch a larger message.';
    private const string CHECK_CRCS_DOC = 'Automatically check the CRC32 of the records consumed. This ensures no on-the-wire or on-disk corruption to the messages occurred. This check adds some overhead, so it may be disabled in cases seeking extreme performance.';
    private const REQUEST_TIMEOUT_MS_DOC = CommonClientConfigs::REQUEST_TIMEOUT_MS_DOC;
    private const string EXCLUDE_INTERNAL_TOPICS_DOC = 'Unsupported Java key. <code>topic.blacklist</code> can filter names, but is not an exact equivalent.';
}
