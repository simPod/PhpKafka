<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients;

use SimPod\Kafka\Common\Config;

//phpcs:disable Cdn77.NamingConventions.ValidConstantName.ClassConstantNotUpperCase
//phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong

/**
 * Native librdkafka configuration keys, including legacy Java keys retained for compatibility.
 * See docs/configuration.md for migration guidance and version-specific defaults.
 */
final class CommonClientConfigs extends Config
{
    /*
     * NOTE: DO NOT CHANGE EITHER CONFIG NAMES AS THESE ARE PART OF THE PUBLIC API AND CHANGE WILL BREAK USER CODE.
     */

    public const string BOOTSTRAP_SERVERS_CONFIG = 'bootstrap.servers';
    public const string BOOTSTRAP_SERVERS_DOC = 'Alias for <code>metadata.broker.list</code>: initial brokers as a comma-separated list of hosts or host:port pairs. This list is used to discover cluster metadata and need not contain every broker.';
    public const string CLIENT_DNS_LOOKUP_CONFIG = 'client.dns.lookup';
    public const string CLIENT_DNS_LOOKUP_DOC = '<p>Controls how the client uses DNS lookups.</p><p>If set to <code>use_all_dns_ips</code> then, when the lookup returns multiple IP addresses for a hostname,'
    . ' they will all be attempted to connect to before failing the connection. Applies to both bootstrap and advertised servers.</p>'
    . '<p>The default is <code>use_all_dns_ips</code>. If the value is <code>resolve_canonical_bootstrap_servers_only</code> each entry will be resolved and expanded into a list of canonical names. Use this value only with GSSAPI (Kerberos).</p>';
    public const string CLIENT_ID_CONFIG = 'client.id';
    public const string CLIENT_ID_DOC = 'An id string to pass to the server when making requests. The purpose of this is to be able to track the source of requests beyond just ip/port by allowing a logical application name to be included in server-side request logging.';
    public const string CONNECTIONS_MAX_IDLE_MS_CONFIG = 'connections.max.idle.ms';
    public const string CONNECTIONS_MAX_IDLE_MS_DOC = 'Close broker connections after the specified inactivity in milliseconds. 0 disables this limit. The native default is 0, with broker-specific heuristics when not explicitly set.';
    public const string DEFAULT_SECURITY_PROTOCOL = 'PLAINTEXT';
    public const string METADATA_MAX_AGE_CONFIG = 'metadata.max.age.ms';
    public const string METADATA_MAX_AGE_DOC = 'Metadata cache maximum age in milliseconds. Defaults to three times <code>topic.metadata.refresh.interval.ms</code> (900000 ms in librdkafka v2.6.1).';

    /** @deprecated Unsupported Java key. Use statistics.interval.ms and RdKafka\Conf::setStatsCb(); no reporter-class equivalent. */
    public const string METRIC_REPORTER_CLASSES_CONFIG = 'metric.reporters';

    /** @deprecated No native reporter-class equivalent. Use statistics.interval.ms and RdKafka\Conf::setStatsCb(). */
    public const string METRIC_REPORTER_CLASSES_DOC = 'Java metrics reporters are unsupported. Configure native statistics with <code>statistics.interval.ms</code> and <code>RdKafka\Conf::setStatsCb()</code>.';

    /** @deprecated Unsupported Java key. No native sampling-count equivalent. */
    public const string METRICS_NUM_SAMPLES_CONFIG = 'metrics.num.samples';

    /** @deprecated No native sampling-count equivalent. */
    public const string METRICS_NUM_SAMPLES_DOC = 'Java metrics sample counts are unsupported; there is no native equivalent.';

    /** @deprecated Unsupported Java key. No native metrics-recording-level equivalent. */
    public const string METRICS_RECORDING_LEVEL_CONFIG = 'metrics.recording.level';

    /** @deprecated No native metrics-recording-level equivalent. */
    public const string METRICS_RECORDING_LEVEL_DOC = 'Java metrics recording levels are unsupported; there is no native equivalent.';

    /** @deprecated Unsupported Java key. No native sampling-window equivalent; statistics.interval.ms controls emission only. */
    public const string METRICS_SAMPLE_WINDOW_MS_CONFIG = 'metrics.sample.window.ms';

    /** @deprecated No native sampling-window equivalent; statistics.interval.ms controls emission only. */
    public const string METRICS_SAMPLE_WINDOW_MS_DOC = 'Java metrics sampling windows are unsupported. <code>statistics.interval.ms</code> controls native statistics emission, not a sampling window.';

    /** @deprecated Unsupported Java key. Use socket.receive.buffer.bytes (0 selects the OS default). */
    public const string RECEIVE_BUFFER_CONFIG = 'receive.buffer.bytes';

    /** @deprecated Use socket.receive.buffer.bytes (0 selects the OS default). */
    public const string RECEIVE_BUFFER_DOC = 'Unsupported Java key. Use <code>socket.receive.buffer.bytes</code> for the broker socket receive buffer in bytes; 0 selects the OS default.';

    /** @deprecated Java lower bound. Native socket.receive.buffer.bytes has a lower bound of 0. */
    public const int RECEIVE_BUFFER_LOWER_BOUND = -1;
    public const string RECONNECT_BACKOFF_MAX_MS_CONFIG = 'reconnect.backoff.max.ms';
    public const string RECONNECT_BACKOFF_MAX_MS_DOC = 'Maximum reconnect backoff in milliseconds. The initial <code>reconnect.backoff.ms</code> increases exponentially up to this value, with -25% to +50% jitter.';
    public const string RECONNECT_BACKOFF_MS_CONFIG = 'reconnect.backoff.ms';
    public const string RECONNECT_BACKOFF_MS_DOC = 'Initial reconnect backoff in milliseconds, with -25% to +50% jitter. Increases exponentially up to <code>reconnect.backoff.max.ms</code>. 0 disables the backoff.';

    /** Producer-only broker acknowledgment timeout. For network timeouts use socket.timeout.ms. */
    public const string REQUEST_TIMEOUT_MS_CONFIG = 'request.timeout.ms';
    public const string REQUEST_TIMEOUT_MS_DOC = 'Producer request acknowledgment timeout in milliseconds, enforced by the broker only when <code>acks</code> is not 0. This is not a consumer or socket timeout.';

    /** Producer-only native alias for message.send.max.retries. */
    public const string RETRIES_CONFIG = 'retries';
    public const string RETRIES_DOC = 'Alias for <code>message.send.max.retries</code>: maximum retries for a failed produced message. Retries can reorder messages unless <code>enable.idempotence=true</code>.';
    public const string RETRY_BACKOFF_MS_CONFIG = 'retry.backoff.ms';
    public const string RETRY_BACKOFF_MS_DOC = 'Initial backoff in milliseconds before retrying a protocol request. In librdkafka v2.6.1 this increases exponentially up to <code>retry.backoff.max.ms</code>.';
    public const string SASL_USERNAME = 'sasl.username';
    public const string SASL_USERNAME_DOC = 'SASL username for use with PLAIN and SCRAM mechanisms.';
    public const string SASL_MECHANISM = 'sasl.mechanisms';
    public const string SASL_MECHANISM_DOC = 'One SASL mechanism to use for authentication: GSSAPI, PLAIN, SCRAM-SHA-256, SCRAM-SHA-512, or OAUTHBEARER. Availability depends on the native build.';
    public const string SASL_PASSWORD = 'sasl.password';
    public const string SASL_PASSWORD_DOC = 'SASL password for use with PLAIN and SCRAM mechanisms.';
    public const string SECURITY_PROTOCOL_CONFIG = 'security.protocol';
    public const string SECURITY_PROTOCOL_DOC = 'Protocol used to communicate with brokers: plaintext (default), ssl, sasl_plaintext, or sasl_ssl. Availability depends on the native build.';

    /** @deprecated Unsupported Java key. Use socket.send.buffer.bytes (0 selects the OS default). */
    public const string SEND_BUFFER_CONFIG = 'send.buffer.bytes';

    /** @deprecated Use socket.send.buffer.bytes (0 selects the OS default). */
    public const string SEND_BUFFER_DOC = 'Unsupported Java key. Use <code>socket.send.buffer.bytes</code> for the broker socket send buffer in bytes; 0 selects the OS default.';

    /** @deprecated Java lower bound. Native socket.send.buffer.bytes has a lower bound of 0. */
    public const int SEND_BUFFER_LOWER_BOUND = -1;

    /** Default network request timeout in milliseconds; consumer fetches add fetch.wait.max.ms. */
    public const string SOCKET_TIMEOUT_MS_CONFIG = 'socket.timeout.ms';

    /** Broker socket send buffer in bytes. 0 selects the OS default. */
    public const string SOCKET_SEND_BUFFER_BYTES_CONFIG = 'socket.send.buffer.bytes';

    /** Broker socket receive buffer in bytes. 0 selects the OS default. */
    public const string SOCKET_RECEIVE_BUFFER_BYTES_CONFIG = 'socket.receive.buffer.bytes';

    /** Maximum Kafka protocol request message size in bytes; the broker enforces its own limit. */
    public const string MESSAGE_MAX_BYTES_CONFIG = 'message.max.bytes';

    /** Native statistics emission interval in milliseconds; also register RdKafka\Conf::setStatsCb(). */
    public const string STATISTICS_INTERVAL_MS_CONFIG = 'statistics.interval.ms';
}
