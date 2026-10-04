<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Producer;

use SimPod\Kafka\Clients\CommonClientConfigs;
use SimPod\Kafka\Common\Config;

use function assert;

//phpcs:disable Cdn77.NamingConventions.ValidConstantName.ClassConstantNotUpperCase
//phpcs:disable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedConstant
//phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
//phpcs:disable SlevomatCodingStandard.TypeHints.ClassConstantTypeHint.MissingNativeTypeHint

/**
 * Native librdkafka producer keys, including legacy Java keys retained for compatibility.
 * See docs/configuration.md for migration guidance and version-specific defaults.
 */
final class ProducerConfig extends Config
{
    /** <code>bootstrap.servers</code> */
    public const BOOTSTRAP_SERVERS_CONFIG = CommonClientConfigs::BOOTSTRAP_SERVERS_CONFIG;

    /** <code>client.dns.lookup</code> */
    public const CLIENT_DNS_LOOKUP_CONFIG = CommonClientConfigs::CLIENT_DNS_LOOKUP_CONFIG;

    /** <code>metadata.max.age.ms</code> */
    public const  METADATA_MAX_AGE_CONFIG = CommonClientConfigs::METADATA_MAX_AGE_CONFIG;

    /** Maximum batch size in bytes, including framing. Applied after the first message; minimum: 1. */
    public const string  BATCH_SIZE_CONFIG = 'batch.size';

    /** <code>acks</code> */
    public const string  ACKS_CONFIG = 'acks';

    /** Native alias for queue.buffering.max.ms. Default: 5 ms in librdkafka v2.6.1. */
    public const string  LINGER_MS_CONFIG = 'linger.ms';

    /** <code>request.timeout.ms</code> */
    public const  REQUEST_TIMEOUT_MS_CONFIG = CommonClientConfigs::REQUEST_TIMEOUT_MS_CONFIG;

    /** Native alias for message.timeout.ms: local delivery timeout including retries; 0 means infinite. */
    public const string  DELIVERY_TIMEOUT_MS_CONFIG = 'delivery.timeout.ms';

    /** <code>client.id</code> */
    public const CLIENT_ID_CONFIG = CommonClientConfigs::CLIENT_ID_CONFIG;

    /** @deprecated Unsupported Java key. Use socket.send.buffer.bytes (0 selects the OS default). */
    public const SEND_BUFFER_CONFIG = CommonClientConfigs::SEND_BUFFER_CONFIG;

    /** @deprecated Unsupported Java key. Use socket.receive.buffer.bytes (0 selects the OS default). */
    public const RECEIVE_BUFFER_CONFIG = CommonClientConfigs::RECEIVE_BUFFER_CONFIG;

    public const SOCKET_SEND_BUFFER_BYTES_CONFIG = CommonClientConfigs::SOCKET_SEND_BUFFER_BYTES_CONFIG;
    public const SOCKET_RECEIVE_BUFFER_BYTES_CONFIG = CommonClientConfigs::SOCKET_RECEIVE_BUFFER_BYTES_CONFIG;
    public const SOCKET_TIMEOUT_MS_CONFIG = CommonClientConfigs::SOCKET_TIMEOUT_MS_CONFIG;
    public const MESSAGE_MAX_BYTES_CONFIG = CommonClientConfigs::MESSAGE_MAX_BYTES_CONFIG;
    public const STATISTICS_INTERVAL_MS_CONFIG = CommonClientConfigs::STATISTICS_INTERVAL_MS_CONFIG;

    /** Maximum messages in the shared producer queue; 0 disables this limit. */
    public const string QUEUE_BUFFERING_MAX_MESSAGES_CONFIG = 'queue.buffering.max.messages';

    /** Maximum total message size in the shared producer queue, in kilobytes; takes priority over the message count. */
    public const string QUEUE_BUFFERING_MAX_KBYTES_CONFIG = 'queue.buffering.max.kbytes';

    /** @deprecated Unsupported Java key. Use message.max.bytes; native framing and enforcement differ. */
    public const string  MAX_REQUEST_SIZE_CONFIG = 'max.request.size';

    /** <code>reconnect.backoff.ms</code> */
    public const RECONNECT_BACKOFF_MS_CONFIG = CommonClientConfigs::RECONNECT_BACKOFF_MS_CONFIG;

    /** <code>reconnect.backoff.max.ms</code> */
    public const RECONNECT_BACKOFF_MAX_MS_CONFIG = CommonClientConfigs::RECONNECT_BACKOFF_MAX_MS_CONFIG;

    /** @deprecated Unsupported Java key. No native global blocking-timeout equivalent; handle queue-full errors in PHP. */
    public const string  MAX_BLOCK_MS_CONFIG = 'max.block.ms';

    /** @deprecated Unsupported Java key. Use queue.buffering.max.kbytes (kilobytes, not bytes); not a total memory cap. */
    public const string  BUFFER_MEMORY_CONFIG = 'buffer.memory';

    /** <code>retry.backoff.ms</code> */
    public const RETRY_BACKOFF_MS_CONFIG = CommonClientConfigs::RETRY_BACKOFF_MS_CONFIG;

    /** <code>compression.type</code> */
    public const string  COMPRESSION_TYPE_CONFIG = 'compression.type';

    /** @deprecated Unsupported Java key. No native sampling-window equivalent; statistics.interval.ms controls emission only. */
    public const METRICS_SAMPLE_WINDOW_MS_CONFIG = CommonClientConfigs::METRICS_SAMPLE_WINDOW_MS_CONFIG;

    /** @deprecated Unsupported Java key. No native sampling-count equivalent. */
    public const METRICS_NUM_SAMPLES_CONFIG = CommonClientConfigs::METRICS_NUM_SAMPLES_CONFIG;

    /**
     * @deprecated Unsupported Java key. No native metrics-recording-level equivalent.
     */
    public const METRICS_RECORDING_LEVEL_CONFIG = CommonClientConfigs::METRICS_RECORDING_LEVEL_CONFIG;

    /** @deprecated Unsupported Java key. Use statistics.interval.ms and RdKafka\Conf::setStatsCb(); no reporter-class equivalent. */
    public const METRIC_REPORTER_CLASSES_CONFIG = CommonClientConfigs::METRIC_REPORTER_CLASSES_CONFIG;

    /** <code>max.in.flight.requests.per.connection</code> */
    public const string  MAX_IN_FLIGHT_REQUESTS_PER_CONNECTION = 'max.in.flight.requests.per.connection';

    /** <code>retries</code> */
    public const  RETRIES_CONFIG = CommonClientConfigs::RETRIES_CONFIG;

    /** @deprecated Unsupported Java key. No native serializer equivalent; serialize the key to bytes in PHP. */
    public const string KEY_SERIALIZER_CLASS_CONFIG = 'key.serializer';
    /** @deprecated No native serializer equivalent; serialize the key to bytes in PHP. */
    public const string KEY_SERIALIZER_CLASS_DOC = 'Java serializer classes are unsupported. Serialize the message key in application code.';

    /** @deprecated Unsupported Java key. No native serializer equivalent; serialize the payload to bytes in PHP. */
    public const string VALUE_SERIALIZER_CLASS_CONFIG = 'value.serializer';
    /** @deprecated No native serializer equivalent; serialize the payload to bytes in PHP. */
    public const string VALUE_SERIALIZER_CLASS_DOC = 'Java serializer classes are unsupported. Serialize the message payload in application code.';

    /** <code>connections.max.idle.ms</code> */
    public const CONNECTIONS_MAX_IDLE_MS_CONFIG = CommonClientConfigs::CONNECTIONS_MAX_IDLE_MS_CONFIG;

    /** @deprecated Unsupported Java key. Use partitioner with a native strategy name (PARTITIONER_CONFIG). */
    public const string  PARTITIONER_CLASS_CONFIG = 'partitioner.class';

    /** Native partitioner name. Default: consistent_random; use murmur2_random for Java-compatible keyed hashing. */
    public const string PARTITIONER_CONFIG = 'partitioner';

    /** @deprecated Unsupported Java key. No PHP interceptor-class equivalent; handle messages in application code. */
    public const string INTERCEPTOR_CLASSES_CONFIG = 'interceptor.classes';
    /** @deprecated No PHP interceptor-class equivalent; handle messages in application code. */
    public const string INTERCEPTOR_CLASSES_DOC = 'Java interceptor classes are unsupported. Native C interceptors are not a PHP class configuration option.';

    /** <code>enable.idempotence</code> */
    public const string ENABLE_IDEMPOTENCE_CONFIG = 'enable.idempotence';
    public const string ENABLE_IDEMPOTENCE_DOC = 'When true, native retries preserve produce order without writing duplicate copies of successfully delivered messages. Default: false. '
    . 'Note that enabling idempotence requires <code>' . self::MAX_IN_FLIGHT_REQUESTS_PER_CONNECTION . '</code> to be less than or equal to 5, '
    . '<code>' . self::RETRIES_CONFIG . '</code> to be greater than 0 and <code>' . self::ACKS_CONFIG . "</code> must be 'all'. If these values "
    . 'are not explicitly set by the user, suitable values will be chosen. If incompatible values are set, '
    . 'native producer creation fails. This protects native retries, not application-level re-sends, and does not guarantee successful delivery.';

    /** <code> transaction.timeout.ms </code> */
    public const string TRANSACTION_TIMEOUT_CONFIG = 'transaction.timeout.ms';
    public const string TRANSACTION_TIMEOUT_DOC = 'The maximum amount of time in ms that the transaction coordinator will wait for a transaction status update from the producer before proactively aborting the ongoing transaction.' .
    ' If this value is larger than the transaction.max.timeout.ms setting in the broker, initTransactions() fails with ERR_INVALID_TRANSACTION_TIMEOUT. Librdkafka adjusts message.timeout.ms and socket.timeout.ms unless explicitly set; explicit values must satisfy the native transaction timeout constraints.';

    /** <code> transactional.id </code> */
    public const string TRANSACTIONAL_ID_CONFIG = 'transactional.id';
    public const string TRANSACTIONAL_ID_DOC = 'Identifies the same transactional producer across restarts, finalizes earlier transactions, and fences obsolete producer instances. No ID is configured by default. Setting an ID enables idempotence automatically unless an incompatible explicit setting is supplied. Requires broker version >= 0.11.0 and native transaction API calls.';

    private const METADATA_MAX_AGE_DOC = CommonClientConfigs::METADATA_MAX_AGE_DOC;
    private const string BATCH_SIZE_DOC = 'Maximum message batch size in bytes, including protocol framing. This limit is applied after the first message, so an oversized first message can still be produced. Also limited by <code>batch.num.messages</code> and <code>message.max.bytes</code>. Minimum: 1; native default: 1000000.';
    private const string ACKS_DOC = 'Alias for <code>request.required.acks</code>: acknowledgments required before the broker responds. 0 means no broker acknowledgment; 1 waits for the leader; -1 or all (native default) waits for all in-sync replicas. With all, fewer replicas than the broker topic min.insync.replicas causes the request to fail.';
    private const string LINGER_MS_DOC = 'Alias for <code>queue.buffering.max.ms</code>: delay in milliseconds to accumulate producer messages into batches. Higher values improve batching at the cost of latency. The default is 5 ms in librdkafka v2.6.1.';
    private const REQUEST_TIMEOUT_MS_DOC = CommonClientConfigs::REQUEST_TIMEOUT_MS_DOC;
    private const string DELIVERY_TIMEOUT_MS_DOC = 'Alias for <code>message.timeout.ms</code>: local delivery timeout including retries. 0 means infinite. Default: 300000 ms; adjusted to transaction.timeout.ms when transactional.id is configured.';
    private const string MAX_REQUEST_SIZE_DOC = 'Unsupported Java key. Use <code>message.max.bytes</code>; native framing can allow a request to exceed the limit by one message, and the broker enforces its own topic limit.';
    private const string MAX_BLOCK_MS_DOC = 'Unsupported Java key. There is no native global blocking-timeout equivalent; handle queue-full errors and retry policy in application code.';
    private const string BUFFER_MEMORY_DOC = 'Unsupported Java key. <code>queue.buffering.max.kbytes</code> limits queued message sizes in kilobytes, not total process memory. <code>queue.buffering.max.messages</code> also limits the message count.';
    private const string COMPRESSION_TYPE_DOC = 'The compression type for all data generated by the producer. The default is none (i.e. no compression). Valid '
    . ' values are <code>none</code>, <code>gzip</code>, <code>snappy</code>, <code>lz4</code>, or <code>zstd</code>. '
    . 'Compression is of full batches of data, so the efficacy of batching will also impact the compression ratio (more batching means better compression).';
    private const string MAX_IN_FLIGHT_REQUESTS_PER_CONNECTION_DOC = 'Maximum in-flight requests per broker connection. Enabling idempotence selects 5 unless explicitly set and requires a value <= 5. Without idempotence, retries with multiple in-flight requests can reorder messages.';
    private const RETRIES_DOC = CommonClientConfigs::RETRIES_DOC;
    private const string PARTITIONER_CLASS_DOC = 'Unsupported Java key. Set <code>partitioner</code> to a native name such as <code>murmur2_random</code> for Java-compatible keyed hashing.';

    public function getConf(): ProducerConf
    {
        $conf = parent::getConf();
        assert($conf instanceof ProducerConf);

        return $conf;
    }

    protected function createConf(): ProducerConf
    {
        return new ProducerConf();
    }
}
