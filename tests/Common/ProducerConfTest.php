<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Common;

use ArrayObject;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RdKafka\Conf;
use RdKafka\Message;
use RdKafka\Producer;
use SimPod\Kafka\Clients\Consumer\ConsumerConfig;
use SimPod\Kafka\Clients\Producer\Exception\DeliveryFailed;
use SimPod\Kafka\Clients\Producer\KafkaProducerWrapper;
use SimPod\Kafka\Clients\Producer\ProducerConfig;

use function spl_object_id;

use const RD_KAFKA_RESP_ERR_NO_ERROR;

final class ProducerConfTest extends TestCase
{
    #[DataProvider('provideProducerConfigPreservesValues')]
    public function testProducerConfigPreservesValues(bool|int|string $value, string $expected): void
    {
        $config = new ProducerConfig();
        $conf = $config->getConf();
        $config->set(ProducerConfig::CLIENT_ID_CONFIG, $value);

        self::assertSame($conf, $config->getConf());
        self::assertSame($expected, $config->get(ProducerConfig::CLIENT_ID_CONFIG));
    }

    /** @return Generator<string, array{bool|int|string, string}> */
    public static function provideProducerConfigPreservesValues(): Generator
    {
        yield 'true' => [true, 'true'];
        yield 'false' => [false, 'false'];
        yield 'string' => ['application', 'application'];
        yield 'integer' => [0, '0'];
    }

    public function testConsumerStillUsesUnmodifiedNativeConf(): void
    {
        $config = new ConsumerConfig();
        $conf = $config->getConf();

        self::assertSame(Conf::class, $conf::class);
        self::assertSame($conf, $config->getConf());
    }

    public function testReusedConfigKeepsCallbackSnapshotsAndProducerFailuresSeparate(): void
    {
        $config = new ProducerConfig();
        $config->set(ProducerConfig::BOOTSTRAP_SERVERS_CONFIG, '127.0.0.1:1');
        $config->set('message.timeout.ms', 100);
        $config->set('log_level', 0);
        $conf = $config->getConf();
        /** @var ArrayObject<int, array{int, int, string|null}> $firstReports */
        $firstReports = new ArrayObject();
        $conf->setDrMsgCb(static function (Producer $producer, Message $message) use ($firstReports): void {
            // phpcs:ignore Cdn77.NamingConventions.ValidVariableName -- Native RdKafka field name.
            $firstReports[] = [spl_object_id($producer), $message->err, $message->topic_name];
        });
        $first = new KafkaProducerWrapper($config);
        $firstNative = $first->getProducer();

        /** @var ArrayObject<int, array{int, int, string|null}> $secondReports */
        $secondReports = new ArrayObject();
        $conf->setDrMsgCb(static function (Producer $producer, Message $message) use ($secondReports): void {
            // phpcs:ignore Cdn77.NamingConventions.ValidVariableName -- Native RdKafka field name.
            $secondReports[] = [spl_object_id($producer), $message->err, $message->topic_name];
        });
        $second = new KafkaProducerWrapper($config);
        $secondNative = $second->getProducer();
        self::assertNotSame($firstNative, $secondNative);

        // Both native producers must exist before replacing the raw configuration callback again.
        $replacementCalls = 0;
        $conf->setDrMsgCb(static function (Producer $producer, Message $message) use (&$replacementCalls): void {
            $replacementCalls++;
        });
        $first->produce('first-config-snapshot', null, 'first');
        self::assertSame(RD_KAFKA_RESP_ERR_NO_ERROR, $firstNative->flush(5000));
        self::assertSame([[spl_object_id($firstNative), -192, 'first-config-snapshot']], $firstReports->getArrayCopy());
        self::assertSame([], $secondReports->getArrayCopy());

        try {
            $first->flushMessages(5000);
            self::fail('The first producer lost its delivery failure.');
        } catch (DeliveryFailed $exception) {
            self::assertSame(-192, $exception->getCode());
            self::assertSame('first-config-snapshot', $exception->topicName);
        }

        // The first report must not poison the second producer before its first enqueue.
        $second->produce('second-config-snapshot', null, 'second');
        self::assertSame(RD_KAFKA_RESP_ERR_NO_ERROR, $secondNative->flush(5000));
        self::assertSame([[spl_object_id($secondNative), -192, 'second-config-snapshot']], $secondReports->getArrayCopy());
        self::assertCount(1, $firstReports);
        self::assertSame(0, $replacementCalls);

        $this->expectException(DeliveryFailed::class);
        $this->expectExceptionCode(-192);
        $this->expectExceptionMessageMatches(
            '~\AKafka delivery failed for topic "second-config-snapshot": Local: Message timed out\z~',
        );

        try {
            $second->flushMessages(5000);
        } catch (DeliveryFailed $exception) {
            self::assertSame('second-config-snapshot', $exception->topicName);

            throw $exception;
        }
    }
}
