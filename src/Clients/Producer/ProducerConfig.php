<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Producer;

use SimPod\Kafka\Clients\CommonClientConfigs;
use SimPod\Kafka\Common\Config;

//phpcs:disable Cdn77.NamingConventions.ValidConstantName.ClassConstantNotUpperCase
//phpcs:disable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedConstant
//phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
//phpcs:disable SlevomatCodingStandard.TypeHints.ClassConstantTypeHint.MissingNativeTypeHint

/**
 * Configuration for the Kafka Producer. Documentation for these configurations can be found in the <a
 * href="http://kafka.apache.org/documentation.html#producerconfigs">Kafka documentation</a>
 */
final class ProducerConfig extends Config
{
    /** <code>bootstrap.servers</code> */
    public const BOOTSTRAP_SERVERS_CONFIG = CommonClientConfigs::BOOTSTRAP_SERVERS_CONFIG;

    /** <code>client.dns.lookup</code> */
    public const CLIENT_DNS_LOOKUP_CONFIG = CommonClientConfigs::CLIENT_DNS_LOOKUP_CONFIG;

    /** <code>metadata.max.age.ms</code> */
    public const  METADATA_MAX_AGE_CONFIG = CommonClientConfigs::METADATA_MAX_AGE_CONFIG;

    /** <code>batch.size</code> */
    public const string  BATCH_SIZE_CONFIG = 'batch.size';

    /** <code>acks</code> */
    public const string  ACKS_CONFIG = 'acks';

    /** <code>linger.ms</code> */
    public const string  LINGER_MS_CONFIG = 'linger.ms';

    /** <code>request.timeout.ms</code> */
    public const  REQUEST_TIMEOUT_MS_CONFIG = CommonClientConfigs::REQUEST_TIMEOUT_MS_CONFIG;

    /** <code>delivery.timeout.ms</code> */
    public const string  DELIVERY_TIMEOUT_MS_CONFIG = 'delivery.timeout.ms';

    /** <code>client.id</code> */
    public const CLIENT_ID_CONFIG = CommonClientConfigs::CLIENT_ID_CONFIG;

    /** <code>send.buffer.bytes</code> */
    public const SEND_BUFFER_CONFIG = CommonClientConfigs::SEND_BUFFER_CONFIG;

    /** <code>receive.buffer.bytes</code> */
    public const RECEIVE_BUFFER_CONFIG = CommonClientConfigs::RECEIVE_BUFFER_CONFIG;

    /** <code>max.request.size</code> */
    public const string  MAX_REQUEST_SIZE_CONFIG = 'max.request.size';

    /** <code>reconnect.backoff.ms</code> */
    public const RECONNECT_BACKOFF_MS_CONFIG = CommonClientConfigs::RECONNECT_BACKOFF_MS_CONFIG;

    /** <code>reconnect.backoff.max.ms</code> */
    public const RECONNECT_BACKOFF_MAX_MS_CONFIG = CommonClientConfigs::RECONNECT_BACKOFF_MAX_MS_CONFIG;

    /** <code>max.block.ms</code> */
    public const string  MAX_BLOCK_MS_CONFIG = 'max.block.ms';

    /** <code>buffer.memory</code> */
    public const string  BUFFER_MEMORY_CONFIG = 'buffer.memory';

    /** <code>retry.backoff.ms</code> */
    public const RETRY_BACKOFF_MS_CONFIG = CommonClientConfigs::RETRY_BACKOFF_MS_CONFIG;

    /** <code>compression.type</code> */
    public const string  COMPRESSION_TYPE_CONFIG = 'compression.type';

    /** <code>metrics.sample.window.ms</code> */
    public const METRICS_SAMPLE_WINDOW_MS_CONFIG = CommonClientConfigs::METRICS_SAMPLE_WINDOW_MS_CONFIG;

    /** <code>metrics.num.samples</code> */
    public const METRICS_NUM_SAMPLES_CONFIG = CommonClientConfigs::METRICS_NUM_SAMPLES_CONFIG;

    /**
     * <code>metrics.recording.level</code>
     */
    public const METRICS_RECORDING_LEVEL_CONFIG = CommonClientConfigs::METRICS_RECORDING_LEVEL_CONFIG;

    /** <code>metric.reporters</code> */
    public const METRIC_REPORTER_CLASSES_CONFIG = CommonClientConfigs::METRIC_REPORTER_CLASSES_CONFIG;

    /** <code>max.in.flight.requests.per.connection</code> */
    public const string  MAX_IN_FLIGHT_REQUESTS_PER_CONNECTION = 'max.in.flight.requests.per.connection';

    /** <code>retries</code> */
    public const  RETRIES_CONFIG = CommonClientConfigs::RETRIES_CONFIG;

    /** <code>key.serializer</code> */
    public const string KEY_SERIALIZER_CLASS_CONFIG = 'key.serializer';
    public const string KEY_SERIALIZER_CLASS_DOC = 'Serializer class for key that implements the <code>org.apache.kafka.common.serialization.Serializer</code> interface.';

    /** <code>value.serializer</code> */
    public const string VALUE_SERIALIZER_CLASS_CONFIG = 'value.serializer';
    public const string VALUE_SERIALIZER_CLASS_DOC = 'Serializer class for value that implements the <code>org.apache.kafka.common.serialization.Serializer</code> interface.';

    /** <code>connections.max.idle.ms</code> */
    public const CONNECTIONS_MAX_IDLE_MS_CONFIG = CommonClientConfigs::CONNECTIONS_MAX_IDLE_MS_CONFIG;

    /** <code>partitioner.class</code> */
    public const string  PARTITIONER_CLASS_CONFIG = 'partitioner.class';

    /** <code>interceptor.classes</code> */
    public const string INTERCEPTOR_CLASSES_CONFIG = 'interceptor.classes';
    public const string INTERCEPTOR_CLASSES_DOC = 'A list of classes to use as interceptors. '
    . 'Implementing the <code>org.apache.kafka.clients.producer.ProducerInterceptor</code> interface allows you to intercept (and possibly mutate) the records '
    . 'received by the producer before they are published to the Kafka cluster. By default, there are no interceptors.';

    /** <code>enable.idempotence</code> */
    public const string ENABLE_IDEMPOTENCE_CONFIG = 'enable.idempotence';
    public const string ENABLE_IDEMPOTENCE_DOC = "When set to 'true', the producer will ensure that exactly one copy of each message is written in the stream. If 'false', producer "
    . 'retries due to broker failures, etc., may write duplicates of the retried message in the stream. '
    . 'Note that enabling idempotence requires <code>' . self::MAX_IN_FLIGHT_REQUESTS_PER_CONNECTION . '</code> to be less than or equal to 5, '
    . '<code>' . self::RETRIES_CONFIG . '</code> to be greater than 0 and <code>' . self::ACKS_CONFIG . "</code> must be 'all'. If these values "
    . 'are not explicitly set by the user, suitable values will be chosen. If incompatible values are set, '
    . 'a <code>ConfigException</code> will be thrown.';

    /** <code> transaction.timeout.ms </code> */
    public const string TRANSACTION_TIMEOUT_CONFIG = 'transaction.timeout.ms';
    public const string TRANSACTION_TIMEOUT_DOC = 'The maximum amount of time in ms that the transaction coordinator will wait for a transaction status update from the producer before proactively aborting the ongoing transaction.' .
    'If this value is larger than the transaction.max.timeout.ms setting in the broker, the request will fail with a <code>InvalidTransactionTimeout</code> error.';

    /** <code> transactional.id </code> */
    public const string TRANSACTIONAL_ID_CONFIG = 'transactional.id';
    public const string TRANSACTIONAL_ID_DOC = 'The TransactionalId to use for transactional delivery. This enables reliability semantics which span multiple producer sessions since it allows the client to guarantee that transactions using the same TransactionalId have been completed prior to starting any new transactions. If no TransactionalId is provided, then the producer is limited to idempotent delivery. ' .
    'Note that <code>enable.idempotence</code> must be enabled if a TransactionalId is configured. ' .
    'The default is <code>null</code>, which means transactions cannot be used. ' .
    'Note that, by default, transactions require a cluster of at least three brokers which is the recommended setting for production; for development you can change this, by adjusting broker setting <code>transaction.state.log.replication.factor</code>.';

    private const METADATA_MAX_AGE_DOC = CommonClientConfigs::METADATA_MAX_AGE_DOC;
    private const string BATCH_SIZE_DOC = 'The producer will attempt to batch records together into fewer requests whenever multiple records are being sent'
    . ' to the same partition. This helps performance on both the client and the server. This configuration controls the '
    . 'default batch size in bytes. '
    . '<p>'
    . 'No attempt will be made to batch records larger than this size. '
    . '<p>'
    . 'Requests sent to brokers will contain multiple batches, one for each partition with data available to be sent. '
    . '<p>'
    . 'A small batch size will make batching less common and may reduce throughput (a batch size of zero will disable '
    . 'batching entirely). A very large batch size may use memory a bit more wastefully as we will always allocate a '
    . 'buffer of the specified batch size in anticipation of additional records.';
    private const string ACKS_DOC = 'The number of acknowledgments the producer requires the leader to have received before considering a request complete. This controls the '
    . ' durability of records that are sent. The following settings are allowed: '
    . ' <ul>'
    . ' <li><code>acks=0</code> If set to zero then the producer will not wait for any acknowledgment from the'
    . ' server at all. The record will be immediately added to the socket buffer and considered sent. No guarantee can be'
    . ' made that the server has received the record in this case, and the <code>retries</code> configuration will not'
    . " take effect (as the client won't generally know of any failures). The offset given back for each record will"
    . ' always be set to -1.'
    . ' <li><code>acks=1</code> This will mean the leader will write the record to its local log but will respond'
    . ' without awaiting full acknowledgement from all followers. In this case should the leader fail immediately after'
    . ' acknowledging the record but before the followers have replicated it then the record will be lost.'
    . ' <li><code>acks=all</code> This means the leader will wait for the full set of in-sync replicas to'
    . ' acknowledge the record. This guarantees that the record will not be lost as long as at least one in-sync replica'
    . ' remains alive. This is the strongest available guarantee. This is equivalent to the acks=-1 setting.';
    private const string LINGER_MS_DOC = 'The producer groups together any records that arrive in between request transmissions into a single batched request. '
    . 'Normally this occurs only under load when records arrive faster than they can be sent out. However in some circumstances the client may want to '
    . 'reduce the number of requests even under moderate load. This setting accomplishes this by adding a small amount '
    . 'of artificial delay&mdash;that is, rather than immediately sending out a record the producer will wait for up to '
    . 'the given delay to allow other records to be sent so that the sends can be batched together. This can be thought '
    . "of as analogous to Nagle's algorithm in TCP. This setting gives the upper bound on the delay for batching: once "
    . 'we get <code>' . self::BATCH_SIZE_CONFIG . '</code> worth of records for a partition it will be sent immediately regardless of this '
    . "setting, however if we have fewer than this many bytes accumulated for this partition we will 'linger' for the "
    . 'specified time waiting for more records to show up. This setting defaults to 0 (i.e. no delay). Setting <code>' . self::LINGER_MS_CONFIG . '=5</code>, '
    . 'for example, would have the effect of reducing the number of requests sent but would add up to 5ms of latency to records sent in the absence of load.';
    private const REQUEST_TIMEOUT_MS_DOC = CommonClientConfigs::REQUEST_TIMEOUT_MS_DOC
    . ' This should be larger than <code>replica.lag.time.max.ms</code> (a broker configuration)'
    . ' to reduce the possibility of message duplication due to unnecessary producer retries.';
    private const string DELIVERY_TIMEOUT_MS_DOC = 'An upper bound on the time to report success or failure '
    . 'after a call to <code>send()</code> returns. This limits the total time that a record will be delayed '
    . 'prior to sending, the time to await acknowledgement from the broker (if expected), and the time allowed '
    . 'for retriable send failures. The producer may report failure to send a record earlier than this config if '
    . 'either an unrecoverable error is encountered, the retries have been exhausted, '
    . 'or the record is added to a batch which reached an earlier delivery expiration deadline. '
    . 'The value of this config should be greater than or equal to the sum of <code>' . self::REQUEST_TIMEOUT_MS_CONFIG . '</code> '
    . 'and <code>' . self::LINGER_MS_CONFIG . '</code>.';
    private const string MAX_REQUEST_SIZE_DOC = 'The maximum size of a request in bytes. This setting will limit the number of record '
    . 'batches the producer will send in a single request to avoid sending huge requests. '
    . 'This is also effectively a cap on the maximum record batch size. Note that the server '
    . 'has its own cap on record batch size which may be different from this.';
    private const string MAX_BLOCK_MS_DOC = 'The configuration controls how long <code>KafkaProducer.send()</code> and <code>KafkaProducer.partitionsFor()</code> will block.'
    . 'These methods can be blocked either because the buffer is full or metadata unavailable.'
    . 'Blocking in the user-supplied serializers or partitioner will not be counted against this timeout.';
    private const string BUFFER_MEMORY_DOC = 'The total bytes of memory the producer can use to buffer records waiting to be sent to the server. If records are '
    . 'sent faster than they can be delivered to the server the producer will block for <code>' . self::MAX_BLOCK_MS_CONFIG . '</code> after which it will throw an exception.'
    . '<p>'
    . 'This setting should correspond roughly to the total memory the producer will use, but is not a hard bound since '
    . 'not all memory the producer uses is used for buffering. Some additional memory will be used for compression (if '
    . 'compression is enabled) as well as for maintaining in-flight requests.';
    private const string COMPRESSION_TYPE_DOC = 'The compression type for all data generated by the producer. The default is none (i.e. no compression). Valid '
    . ' values are <code>none</code>, <code>gzip</code>, <code>snappy</code>, <code>lz4</code>, or <code>zstd</code>. '
    . 'Compression is of full batches of data, so the efficacy of batching will also impact the compression ratio (more batching means better compression).';
    private const string MAX_IN_FLIGHT_REQUESTS_PER_CONNECTION_DOC = 'The maximum number of unacknowledged requests the client will send on a single connection before blocking.'
    . ' Note that if this setting is set to be greater than 1 and there are failed sends, there is a risk of'
    . ' message re-ordering due to retries (i.e., if retries are enabled).';
    private const string RETRIES_DOC = 'Setting a value greater than zero will cause the client to resend any record whose send fails with a potentially transient error.'
    . ' Note that this retry is no different than if the client resent the record upon receiving the error.'
    . ' Allowing retries without setting <code>' . self::MAX_IN_FLIGHT_REQUESTS_PER_CONNECTION . '</code> to 1 will potentially change the'
    . ' ordering of records because if two batches are sent to a single partition, and the first fails and is retried but the second'
    . ' succeeds, then the records in the second batch may appear first. Note additionall that produce requests will be'
    . ' failed before the number of retries has been exhausted if the timeout configured by'
    . ' <code>' . self::DELIVERY_TIMEOUT_MS_CONFIG . '</code> expires first before successful acknowledgement. Users should generally'
    . ' prefer to leave this config unset and instead use <code>' . self::DELIVERY_TIMEOUT_MS_CONFIG . '</code> to control'
    . ' retry behavior.';
    private const string PARTITIONER_CLASS_DOC = 'Partitioner class that implements the <code>org.apache.kafka.clients.producer.Partitioner</code> interface.';
}
