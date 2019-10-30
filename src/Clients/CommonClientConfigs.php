<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients;

use SimPod\Kafka\Common\Config;

/**
 * Some configurations shared by both producer and consumer
 */
final class CommonClientConfigs extends Config
{
    /*
     * NOTE: DO NOT CHANGE EITHER CONFIG NAMES AS THESE ARE PART OF THE PUBLIC API AND CHANGE WILL BREAK USER CODE.
     */

    public const BOOTSTRAP_SERVERS_CONFIG        = 'bootstrap.servers';
    public const BOOTSTRAP_SERVERS_DOC           = 'A list of host/port pairs to use for establishing the initial connection to the Kafka cluster. The client will make use of all servers irrespective of which servers are specified here for bootstrapping&mdash;this list only impacts the initial hosts used to discover the full set of servers. This list should be in the form '
    . '<code>host1:port1,host2:port2,...</code>. Since these servers are just used for the initial connection to '
    . 'discover the full cluster membership (which may change dynamically), this list need not contain the full set of '
    . 'servers (you may want more than one, though, in case a server is down).';
    public const CLIENT_DNS_LOOKUP_CONFIG        = 'client.dns.lookup';
    public const CLIENT_DNS_LOOKUP_DOC           = '<p>Controls how the client uses DNS lookups.</p><p>If set to <code>use_all_dns_ips</code> then, when the lookup returns multiple IP addresses for a hostname,'
    . ' they will all be attempted to connect to before failing the connection. Applies to both bootstrap and advertised servers.</p>'
    . '<p>If the value is <code>resolve_canonical_bootstrap_servers_only</code> each entry will be resolved and expanded into a list of canonical names.</p>';
    public const CLIENT_ID_CONFIG                = 'client.id';
    public const CLIENT_ID_DOC                   = 'An id string to pass to the server when making requests. The purpose of this is to be able to track the source of requests beyond just ip/port by allowing a logical application name to be included in server-side request logging.';
    public const CONNECTIONS_MAX_IDLE_MS_CONFIG  = 'connections.max.idle.ms';
    public const CONNECTIONS_MAX_IDLE_MS_DOC     = 'Close idle connections after the number of milliseconds specified by this config.';
    public const DEFAULT_SECURITY_PROTOCOL       = 'PLAINTEXT';
    public const METADATA_MAX_AGE_CONFIG         = 'metadata.max.age.ms';
    public const METADATA_MAX_AGE_DOC            = "The period of time in milliseconds after which we force a refresh of metadata even if we haven't seen any partition leadership changes to proactively discover any new brokers or partitions.";
    public const METRIC_REPORTER_CLASSES_CONFIG  = 'metric.reporters';
    public const METRIC_REPORTER_CLASSES_DOC     = 'A list of classes to use as metrics reporters. Implementing the <code>org.apache.kafka.common.metrics.MetricsReporter</code> interface allows plugging in classes that will be notified of new metric creation. The JmxReporter is always included to register JMX statistics.';
    public const METRICS_NUM_SAMPLES_CONFIG      = 'metrics.num.samples';
    public const METRICS_NUM_SAMPLES_DOC         = 'The number of samples maintained to compute metrics.';
    public const METRICS_RECORDING_LEVEL_CONFIG  = 'metrics.recording.level';
    public const METRICS_RECORDING_LEVEL_DOC     = 'The highest recording level for metrics.';
    public const METRICS_SAMPLE_WINDOW_MS_CONFIG = 'metrics.sample.window.ms';
    public const METRICS_SAMPLE_WINDOW_MS_DOC    = 'The window of time a metrics sample is computed over.';
    public const RECEIVE_BUFFER_CONFIG           = 'receive.buffer.bytes';
    public const RECEIVE_BUFFER_DOC              = 'The size of the TCP receive buffer (SO_RCVBUF) to use when reading data. If the value is -1, the OS default will be used.';
    public const RECEIVE_BUFFER_LOWER_BOUND      = -1;
    public const RECONNECT_BACKOFF_MAX_MS_CONFIG = 'reconnect.backoff.max.ms';
    public const RECONNECT_BACKOFF_MAX_MS_DOC    = 'The maximum amount of time in milliseconds to wait when reconnecting to a broker that has repeatedly failed to connect. If provided, the backoff per host will increase exponentially for each consecutive connection failure, up to this maximum. After calculating the backoff increase, 20% random jitter is added to avoid connection storms.';
    public const RECONNECT_BACKOFF_MS_CONFIG     = 'reconnect.backoff.ms';
    public const RECONNECT_BACKOFF_MS_DOC        = 'The base amount of time to wait before attempting to reconnect to a given host. This avoids repeatedly connecting to a host in a tight loop. This backoff applies to all connection attempts by the client to a broker.';
    public const REQUEST_TIMEOUT_MS_CONFIG       = 'request.timeout.ms';
    public const REQUEST_TIMEOUT_MS_DOC          = 'The configuration controls the maximum amount of time the client will wait '
    . 'for the response of a request. If the response is not received before the timeout '
    . 'elapses the client will resend the request if necessary or fail the request if '
    . 'retries are exhausted.';
    public const RETRIES_CONFIG                  = 'retries';
    public const RETRIES_DOC                     = 'Setting a value greater than zero will cause the client to resend any request that fails with a potentially transient error.';
    public const RETRY_BACKOFF_MS_CONFIG         = 'retry.backoff.ms';
    public const RETRY_BACKOFF_MS_DOC            = 'The amount of time to wait before attempting to retry a failed request to a given topic partition. This avoids repeatedly sending requests in a tight loop under some failure scenarios.';
    public const SASL_USERNAME                   = 'sasl.username';
    public const SASL_USERNAME_DOC               = 'SASL username for use with the PLAIN mechanism.';
    public const SASL_MECHANISM                  = 'sasl.mechanisms';
    public const SASL_MECHANISM_DOC              = 'SASL mechanism to use for authentication. Supported: GSSAPI, PLAIN.';
    public const SASL_PASSWORD                   = 'sasl.password';
    public const SASL_PASSWORD_DOC               = 'SASL password for use with the PLAIN mechanism.';
    public const SECURITY_PROTOCOL_CONFIG        = 'security.protocol';
    public const SECURITY_PROTOCOL_DOC           = 'Protocol used to communicate with brokers. Valid values are: '; // TODO
    public const SEND_BUFFER_CONFIG              = 'send.buffer.bytes';
    public const SEND_BUFFER_DOC                 = 'The size of the TCP send buffer (SO_SNDBUF) to use when sending data. If the value is -1, the OS default will be used.';
    public const SEND_BUFFER_LOWER_BOUND         = -1;
}
