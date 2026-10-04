<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Common;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RdKafka\Exception;
use RdKafka\TopicConf;
use SimPod\Kafka\Clients\CommonClientConfigs;
use SimPod\Kafka\Clients\Consumer\ConsumerConfig;
use SimPod\Kafka\Clients\Producer\ProducerConfig;
use SimPod\Kafka\Common\Config;

final class ConfigTest extends TestCase
{
    #[DataProvider('providerSet')]
    public function testSet(bool|int|string $value, string $expected): void
    {
        $config = new ConsumerConfig();
        $config->set(ConsumerConfig::GROUP_ID_CONFIG, $value);

        self::assertSame($expected, $config->get(ConsumerConfig::GROUP_ID_CONFIG));
    }

    /** @return Generator<array{mixed, string}> */
    public static function providerSet(): Generator
    {
        yield [true, 'true'];
        yield [false, 'false'];
        yield ['string', 'string'];
        yield [0, '0'];
    }

    /** @param class-string<Config> $configClass */
    #[DataProvider('provideNativeGlobalSettings')]
    public function testNativeGlobalSettings(
        string $configClass,
        string $key,
        bool|int|string $value,
        string $nativeKey,
        string $expected,
    ): void {
        $config = new $configClass();
        $config->set($key, $value);

        self::assertSame($expected, $config->get($nativeKey));
    }

    /** @phpstan-return Generator<string, array{class-string<Config>, string, bool|int|string, string, string}> */
    public static function provideNativeGlobalSettings(): Generator
    {
        yield 'manual offset storage' => [
            ConsumerConfig::class,
            ConsumerConfig::ENABLE_AUTO_OFFSET_STORE_CONFIG,
            false,
            'enable.auto.offset.store',
            'false',
        ];

        yield 'native fetch wait' => [
            ConsumerConfig::class,
            ConsumerConfig::FETCH_WAIT_MAX_MS_CONFIG,
            123,
            'fetch.wait.max.ms',
            '123',
        ];

        yield 'consumer prefetch count' => [
            ConsumerConfig::class,
            ConsumerConfig::QUEUED_MIN_MESSAGES_CONFIG,
            50,
            'queued.min.messages',
            '50',
        ];

        yield 'consumer prefetch kilobytes' => [
            ConsumerConfig::class,
            ConsumerConfig::QUEUED_MAX_MESSAGES_KBYTES_CONFIG,
            1024,
            'queued.max.messages.kbytes',
            '1024',
        ];

        yield 'native assignment name' => [
            ConsumerConfig::class,
            ConsumerConfig::PARTITION_ASSIGNMENT_STRATEGY_CONFIG,
            'roundrobin',
            'partition.assignment.strategy',
            'roundrobin',
        ];

        yield 'partition fetch alias' => [
            ConsumerConfig::class,
            ConsumerConfig::MAX_PARTITION_FETCH_BYTES_CONFIG,
            2097152,
            'fetch.message.max.bytes',
            '2097152',
        ];

        yield 'producer queue count' => [
            ProducerConfig::class,
            ProducerConfig::QUEUE_BUFFERING_MAX_MESSAGES_CONFIG,
            75,
            'queue.buffering.max.messages',
            '75',
        ];

        yield 'producer queue kilobytes' => [
            ProducerConfig::class,
            ProducerConfig::QUEUE_BUFFERING_MAX_KBYTES_CONFIG,
            4096,
            'queue.buffering.max.kbytes',
            '4096',
        ];

        yield 'native request size' => [
            ProducerConfig::class,
            ProducerConfig::MESSAGE_MAX_BYTES_CONFIG,
            2097152,
            'message.max.bytes',
            '2097152',
        ];

        yield 'linger alias' => [
            ProducerConfig::class,
            ProducerConfig::LINGER_MS_CONFIG,
            12,
            'queue.buffering.max.ms',
            '12',
        ];

        yield 'retry alias' => [
            ProducerConfig::class,
            ProducerConfig::RETRIES_CONFIG,
            7,
            'message.send.max.retries',
            '7',
        ];

        yield 'compression alias' => [
            ProducerConfig::class,
            ProducerConfig::COMPRESSION_TYPE_CONFIG,
            'lz4',
            'compression.codec',
            'lz4',
        ];

        yield 'bootstrap alias' => [
            CommonClientConfigs::class,
            CommonClientConfigs::BOOTSTRAP_SERVERS_CONFIG,
            '127.0.0.1:9092',
            'metadata.broker.list',
            '127.0.0.1:9092',
        ];

        yield 'socket send buffer' => [
            CommonClientConfigs::class,
            CommonClientConfigs::SOCKET_SEND_BUFFER_BYTES_CONFIG,
            65536,
            'socket.send.buffer.bytes',
            '65536',
        ];

        yield 'socket receive buffer' => [
            CommonClientConfigs::class,
            CommonClientConfigs::SOCKET_RECEIVE_BUFFER_BYTES_CONFIG,
            65536,
            'socket.receive.buffer.bytes',
            '65536',
        ];

        yield 'socket timeout' => [
            CommonClientConfigs::class,
            CommonClientConfigs::SOCKET_TIMEOUT_MS_CONFIG,
            15000,
            'socket.timeout.ms',
            '15000',
        ];

        yield 'statistics emission' => [
            CommonClientConfigs::class,
            CommonClientConfigs::STATISTICS_INTERVAL_MS_CONFIG,
            1000,
            'statistics.interval.ms',
            '1000',
        ];
    }

    /** @param class-string<Config> $configClass */
    #[DataProvider('provideNativeTopicSettings')]
    public function testNativeTopicSettings(
        string $configClass,
        string $key,
        string $value,
        string $nativeKey,
        string $expected,
    ): void {
        $config = new $configClass();
        $config->set($key, $value);

        // Global dumps omit topic settings. Check their canonical values through the native topic API.
        $topicConf = new TopicConf();
        $topicConf->set($key, $value);

        self::assertSame($expected, $topicConf->dump()[$nativeKey]);
    }

    /** @phpstan-return Generator<string, array{class-string<Config>, string, string, string, string}> */
    public static function provideNativeTopicSettings(): Generator
    {
        yield 'offset reset error' => [
            ConsumerConfig::class,
            ConsumerConfig::AUTO_OFFSET_RESET_CONFIG,
            'error',
            'auto.offset.reset',
            'error',
        ];

        yield 'Java-compatible partitioner' => [
            ProducerConfig::class,
            ProducerConfig::PARTITIONER_CONFIG,
            'murmur2_random',
            'partitioner',
            'murmur2_random',
        ];

        yield 'delivery timeout alias' => [
            ProducerConfig::class,
            ProducerConfig::DELIVERY_TIMEOUT_MS_CONFIG,
            '12345',
            'message.timeout.ms',
            '12345',
        ];

        yield 'acknowledgment alias' => [
            ProducerConfig::class,
            ProducerConfig::ACKS_CONFIG,
            'all',
            'request.required.acks',
            '-1',
        ];
    }

    /** @param class-string<Config> $configClass */
    #[DataProvider('provideNativeValidationRejectsJavaSettings')]
    public function testNativeValidationRejectsJavaSettings(string $configClass, string $key, string $value): void
    {
        $config = new $configClass();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($key);

        $config->set($key, $value);
    }

    /** @phpstan-return Generator<string, array{class-string<Config>, string, string}> */
    public static function provideNativeValidationRejectsJavaSettings(): Generator
    {
        yield 'Java fetch wait key is not translated' => [
            ConsumerConfig::class,
            ConsumerConfig::FETCH_MAX_WAIT_MS_CONFIG,
            '100',
        ];

        yield 'Java partitioner class key is not translated' => [
            ProducerConfig::class,
            ProducerConfig::PARTITIONER_CLASS_CONFIG,
            'org.apache.kafka.clients.producer.internals.DefaultPartitioner',
        ];

        yield 'Java offset reset value is not translated' => [
            ConsumerConfig::class,
            ConsumerConfig::AUTO_OFFSET_RESET_CONFIG,
            'none',
        ];
    }
}
