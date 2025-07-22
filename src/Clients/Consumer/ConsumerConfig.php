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
 * The consumer configuration keys
 */
final class ConsumerConfig extends Config
{
    /**
     * <code>group.id</code>
     */
    public const string  GROUP_ID_CONFIG = 'group.id';

    /** <code>max.poll.records</code> */
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

    /**
     * <code>auto.commit.interval.ms</code>
     */
    public const string  AUTO_COMMIT_INTERVAL_MS_CONFIG = 'auto.commit.interval.ms';

    /**
     * <code>partition.assignment.strategy</code>
     */
    public const string  PARTITION_ASSIGNMENT_STRATEGY_CONFIG = 'partition.assignment.strategy';

    /**
     * <code>auto.offset.reset</code>
     */
    public const string AUTO_OFFSET_RESET_CONFIG = 'auto.offset.reset';
    public const string AUTO_OFFSET_RESET_DOC = "What to do when there is no initial offset in Kafka or if the current offset does not exist any more on the server (e.g. because that data has been deleted): <ul><li>earliest: automatically reset the offset to the earliest offset<li>latest: automatically reset the offset to the latest offset</li><li>none: throw exception to the consumer if no previous offset is found for the consumer's group</li><li>anything else: throw exception to the consumer.</li></ul>";

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
     * <code>fetch.max.wait.ms</code>
     */
    public const string  FETCH_MAX_WAIT_MS_CONFIG = 'fetch.max.wait.ms';

    /** <code>metadata.max.age.ms</code> */
    public const METADATA_MAX_AGE_CONFIG = CommonClientConfigs::METADATA_MAX_AGE_CONFIG;

    /**
     * <code>max.partition.fetch.bytes</code>
     */
    public const string  MAX_PARTITION_FETCH_BYTES_CONFIG = 'max.partition.fetch.bytes';

    public const int  DEFAULT_MAX_PARTITION_FETCH_BYTES = 1 * 1024 * 1024;

    /** <code>send.buffer.bytes</code> */
    public const SEND_BUFFER_CONFIG = CommonClientConfigs::SEND_BUFFER_CONFIG;

    /** <code>receive.buffer.bytes</code> */
    public const RECEIVE_BUFFER_CONFIG = CommonClientConfigs::RECEIVE_BUFFER_CONFIG;

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
     * <code>metrics.sample.window.ms</code>
     */
    public const METRICS_SAMPLE_WINDOW_MS_CONFIG = CommonClientConfigs::METRICS_SAMPLE_WINDOW_MS_CONFIG;

    /**
     * <code>metrics.num.samples</code>
     */
    public const METRICS_NUM_SAMPLES_CONFIG = CommonClientConfigs::METRICS_NUM_SAMPLES_CONFIG;

    /**
     * <code>metrics.log.level</code>
     */
    public const METRICS_RECORDING_LEVEL_CONFIG = CommonClientConfigs::METRICS_RECORDING_LEVEL_CONFIG;

    /**
     * <code>metric.reporters</code>
     */
    public const METRIC_REPORTER_CLASSES_CONFIG = CommonClientConfigs::METRIC_REPORTER_CLASSES_CONFIG;

    /**
     * <code>check.crcs</code>
     */
    public const string  CHECK_CRCS_CONFIG = 'check.crcs';

    /** <code>key.deserializer</code> */
    public const string KEY_DESERIALIZER_CLASS_CONFIG = 'key.deserializer';
    public const string KEY_DESERIALIZER_CLASS_DOC = 'Deserializer class for key that implements the <code>org.apache.kafka.common.serialization.Deserializer</code> interface.';

    /** <code>value.deserializer</code> */
    public const string VALUE_DESERIALIZER_CLASS_CONFIG = 'value.deserializer';
    public const string VALUE_DESERIALIZER_CLASS_DOC = 'Deserializer class for value that implements the <code>org.apache.kafka.common.serialization.Deserializer</code> interface.';

    /** <code>connections.max.idle.ms</code> */
    public const CONNECTIONS_MAX_IDLE_MS_CONFIG = CommonClientConfigs::CONNECTIONS_MAX_IDLE_MS_CONFIG;

    /** <code>request.timeout.ms</code> */
    public const  REQUEST_TIMEOUT_MS_CONFIG = CommonClientConfigs::REQUEST_TIMEOUT_MS_CONFIG;

    /** <code>default.api.timeout.ms</code> */
    public const string DEFAULT_API_TIMEOUT_MS_CONFIG = 'default.api.timeout.ms';
    public const string DEFAULT_API_TIMEOUT_MS_DOC = 'Specifies the timeout (in milliseconds) for consumer APIs that could block. This configuration is used as the default timeout for all consumer operations that do not explicitly accept a <code>timeout</code> parameter.';

    /** <code>interceptor.classes</code> */
    public const string INTERCEPTOR_CLASSES_CONFIG = 'interceptor.classes';
    public const string INTERCEPTOR_CLASSES_DOC = 'A list of classes to use as interceptors. '
    . 'Implementing the <code>org.apache.kafka.clients.consumer.ConsumerInterceptor</code> interface allows you to intercept (and possibly mutate) records '
    . 'received by the consumer. By default, there are no interceptors.';

    /** <code>exclude.internal.topics</code> */
    public const string  EXCLUDE_INTERNAL_TOPICS_CONFIG = 'exclude.internal.topics';

    public const true  DEFAULT_EXCLUDE_INTERNAL_TOPICS = true;

    /**
     * <code>internal.leave.group.on.close</code>
     * Whether or not the consumer should leave the group on close. If set to <code>false</code> then a rebalance
     * won't occur until <code>session.timeout.ms</code> expires.
     *
     * <p>
     * Note: this is an internal configuration and could be changed in the future in a backward incompatible way
     */
    public const string LEAVE_GROUP_ON_CLOSE_CONFIG = 'internal.leave.group.on.close';

    /** <code>isolation.level</code> */
    public const string ISOLATION_LEVEL_CONFIG = 'isolation.level';
    public const string ISOLATION_LEVEL_DOC = '<p>Controls how to read messages written transactionally. If set to <code>read_committed</code>, consumer.poll() will only return' .
    " transactional messages which have been committed. If set to <code>read_uncommitted</code>' (the default), consumer.poll() will return all messages, even transactional messages" .
    ' which have been aborted. Non-transactional messages will be returned unconditionally in either mode.</p> <p>Messages will always be returned in offset order. Hence, in ' .
    ' <code>read_committed</code> mode, consumer.poll() will only return messages up to the last stable offset (LSO), which is the one less than the offset of the first open transaction.' .
    ' In particular any messages appearing after messages belonging to ongoing transactions will be withheld until the relevant transaction has been completed. As a result, <code>read_committed</code>' .
    ' consumers will not be able to read up to the high watermark when there are in flight transactions.</p><p> Further, when in <code>read_committed</code> the seekToEnd method will' .
    ' return the LSO';
    private const string GROUP_ID_DOC = 'A unique string that identifies the consumer group this consumer belongs to. This property is required if the consumer uses either the group management functionality by using <code>subscribe(topic)</code> or the Kafka-based offset management strategy.';
    private const string MAX_POLL_RECORDS_DOC = 'The maximum number of records returned in a single call to poll().';
    private const string MAX_POLL_INTERVAL_MS_DOC = 'The maximum delay between invocations of poll() when using ' .
    'consumer group management. This places an upper bound on the amount of time that the consumer can be idle ' .
    'before fetching more records. If poll() is not called before expiration of this timeout, then the consumer ' .
    'is considered failed and the group will rebalance in order to reassign the partitions to another member. ';
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
    private const string PARTITION_ASSIGNMENT_STRATEGY_DOC = 'The class name of the partition assignment strategy that the client will use to distribute partition ownership amongst consumer instances when group management is used';
    private const string FETCH_MIN_BYTES_DOC = 'The minimum amount of data the server should return for a fetch request. If insufficient data is available the request will wait for that much data to accumulate before answering the request. The default setting of 1 byte means that fetch requests are answered as soon as a single byte of data is available or the fetch request times out waiting for data to arrive. Setting this to something greater than 1 will cause the server to wait for larger amounts of data to accumulate which can improve server throughput a bit at the cost of some additional latency.';
    private const string FETCH_MAX_BYTES_DOC = 'The maximum amount of data the server should return for a fetch request. ' .
    'Records are fetched in batches by the consumer, and if the first record batch in the first non-empty partition of the fetch is larger than ' .
    'this value, the record batch will still be returned to ensure that the consumer can make progress. As such, this is not a absolute maximum. ' .
    'The maximum record batch size accepted by the broker is defined via <code>message.max.bytes</code> (broker config) or ' .
    '<code>max.message.bytes</code> (topic config). Note that the consumer performs multiple fetches in parallel.';
    private const string FETCH_MAX_WAIT_MS_DOC = "The maximum amount of time the server will block before answering the fetch request if there isn't sufficient data to immediately satisfy the requirement given by fetch.min.bytes.";
    private const string MAX_PARTITION_FETCH_BYTES_DOC = 'The maximum amount of data per-partition the server ' .
    'will return. Records are fetched in batches by the consumer. If the first record batch in the first non-empty ' .
    'partition of the fetch is larger than this limit, the ' .
    'batch will still be returned to ensure that the consumer can make progress. The maximum record batch size ' .
    'accepted by the broker is defined via <code>message.max.bytes</code> (broker config) or ' .
    '<code>max.message.bytes</code> (topic config). See ' . self::FETCH_MAX_BYTES_CONFIG . ' for limiting the consumer request size.';
    private const string CHECK_CRCS_DOC = 'Automatically check the CRC32 of the records consumed. This ensures no on-the-wire or on-disk corruption to the messages occurred. This check adds some overhead, so it may be disabled in cases seeking extreme performance.';
    private const REQUEST_TIMEOUT_MS_DOC = CommonClientConfigs::REQUEST_TIMEOUT_MS_DOC;
    private const string EXCLUDE_INTERNAL_TOPICS_DOC = 'Whether records from internal topics (such as offsets) should be exposed to the consumer. '
    . 'If set to <code>true</code> the only way to receive records from an internal topic is subscribing to it.';
}
