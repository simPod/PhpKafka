<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Clients\Producer;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RdKafka\Exception as RdKafkaException;
use RdKafka\Message;
use RdKafka\Producer;
use SimPod\Kafka\Clients\Producer\Exception\DeliveryFailed;
use SimPod\Kafka\Clients\Producer\KafkaProducer;
use SimPod\Kafka\Clients\Producer\KafkaProducerWrapper;
use SimPod\Kafka\Clients\Producer\ProducerConfig;
use WeakReference;

use function spl_object_id;
use function usleep;

use const RD_KAFKA_PARTITION_UA;
use const RD_KAFKA_RESP_ERR_NO_ERROR;

final class KafkaProducerWrapperTest extends TestCase
{
    #[DataProvider('provideNativeFlushDoesNotHideDeliveryFailure')]
    public function testNativeFlushDoesNotHideDeliveryFailure(string $flavor): void
    {
        $config = self::createConfig();
        $producer = $flavor === 'wrapper' ? new KafkaProducerWrapper($config) : new KafkaProducer($config);
        $native = $producer instanceof KafkaProducerWrapper ? $producer->getProducer() : $producer;
        $producer->produce('delivery-failed', null, 'payload');

        // Native flush reports an empty queue, even though the delivery report is a failure.
        self::assertSame(RD_KAFKA_RESP_ERR_NO_ERROR, $native->flush(5000));
        self::assertSame(0, $native->getOutQLen());

        $failure = self::flushFailure($producer);
        self::assertSame(-192, $failure->getCode());
        self::assertSame('delivery-failed', $failure->topicName);
        self::assertSame(
            'Kafka delivery failed for topic "delivery-failed": Local: Message timed out',
            $failure->getMessage(),
        );
        self::assertSame($failure->getMessage(), self::flushFailure($producer)->getMessage());

        // An explicitly observed failure remains visible outside destruction.
        $this->expectException(DeliveryFailed::class);
        $this->expectExceptionCode(-192);
        $this->expectExceptionMessageIs($failure->getMessage());
        $producer->produce('must-not-be-enqueued', null, 'payload');
    }

    /** @return Generator<string, array{string}> */
    public static function provideNativeFlushDoesNotHideDeliveryFailure(): Generator
    {
        yield 'wrapper' => ['wrapper'];
        yield 'deprecated producer' => ['deprecated'];
    }

    public function testRawNativePublishingIsObserved(): void
    {
        $producer = new KafkaProducerWrapper(self::createConfig());
        $native = $producer->getProducer();
        self::assertSame($native, $producer->getProducer());
        $native->newTopic('raw-delivery-failed')->producev(RD_KAFKA_PARTITION_UA, 0, 'raw payload');

        self::assertSame(RD_KAFKA_RESP_ERR_NO_ERROR, $native->flush(5000));
        $failure = self::flushFailure($producer);
        self::assertSame(-192, $failure->getCode());
        self::assertSame('raw-delivery-failed', $failure->topicName);
    }

    public function testErrorOnlyReportsAreAccepted(): void
    {
        $config = self::createConfig();
        $config->set('delivery.report.only.error', true);
        $reports = [];
        $config->getConf()->setDrMsgCb(static function (Producer $producer, Message $message) use (&$reports): void {
            $reports[] = $message->err;
        });
        $producer = new KafkaProducerWrapper($config);
        $producer->produce('error-only-delivery', null, 'payload');

        $failure = self::flushFailure($producer);
        self::assertSame([-192], $reports);
        self::assertSame(-192, $failure->getCode());
        self::assertSame('error-only-delivery', $failure->topicName);
    }

    public function testCallbackMutationCannotHideDeliveryFailure(): void
    {
        $config = self::createConfig();
        $reports = [];
        $config->getConf()->setDrMsgCb(static function (Producer $producer, Message $message) use (&$reports): void {
            $reports[] = $message->err;
            $message->err = RD_KAFKA_RESP_ERR_NO_ERROR;
        });
        $producer = new KafkaProducerWrapper($config);
        $producer->produce('mutated-delivery-report', null, 'payload');

        $failure = self::flushFailure($producer);
        self::assertSame([-192], $reports);
        self::assertSame(-192, $failure->getCode());
        self::assertSame('mutated-delivery-report', $failure->topicName);
        self::assertSame(
            'Kafka delivery failed for topic "mutated-delivery-report": Local: Message timed out',
            $failure->getMessage(),
        );
    }

    public function testSuccessfulFlushDeliversAcknowledgedReports(): void
    {
        $config = self::createConfig('127.0.0.1:9092', 10000, 'all');
        $reports = [];
        $config->getConf()->setDrMsgCb(static function (Producer $producer, Message $message) use (&$reports): void {
            // phpcs:ignore Cdn77.NamingConventions.ValidVariableName -- Native RdKafka field name.
            $reports[] = [$message->err, $message->topic_name, $message->payload];
        });
        $producer = new KafkaProducerWrapper($config);
        $producer->produce('acknowledged-delivery', 0, 'first');
        $producer->produce('acknowledged-delivery', 0, 'second');
        $producer->flushMessages(5000);

        self::assertSame([
            [RD_KAFKA_RESP_ERR_NO_ERROR, 'acknowledged-delivery', 'first'],
            [RD_KAFKA_RESP_ERR_NO_ERROR, 'acknowledged-delivery', 'second'],
        ], $reports);
        self::assertSame(0, $producer->getProducer()->getOutQLen());
    }

    #[DataProvider('provideApplicationCallbackExceptionIsPreserved')]
    public function testApplicationCallbackExceptionIsPreserved(string $operation): void
    {
        $config = self::createConfig('127.0.0.1:9092', 10000, 'all');
        $expected = new RdKafkaException('Application callback failed');
        $config->getConf()->setDrMsgCb(static function (Producer $producer, Message $message) use ($expected): void {
            self::assertSame(RD_KAFKA_RESP_ERR_NO_ERROR, $message->err);

            throw $expected;
        });
        $producer = new KafkaProducerWrapper($config);
        $producer->getProducer()->newTopic('application-callback-exception')->producev(
            RD_KAFKA_PARTITION_UA,
            0,
            'payload',
        );

        try {
            if ($operation === 'flush') {
                $producer->flushMessages(5000);
            } else {
                // Let a real broker report reach produce()'s non-blocking native poll.
                for ($attempt = 0; $attempt < 50; $attempt++) {
                    usleep(100000);
                    $producer->produce('application-callback-exception', null, 'payload');
                }
            }
        } catch (RdKafkaException $exception) {
            self::assertSame($expected, $exception);

            return;
        }

        self::fail('The application callback exception did not propagate.');
    }

    /** @return Generator<string, array{string}> */
    public static function provideApplicationCallbackExceptionIsPreserved(): Generator
    {
        yield 'native flush' => ['flush'];
        yield 'native poll in produce' => ['poll'];
    }

    public function testUnusedLazyWrapperRunsExitCallback(): void
    {
        $exited = false;
        $producer = new KafkaProducerWrapper(
            self::createConfig(),
            static function (KafkaProducerWrapper $producer) use (&$exited): void {
                $producer->flushMessages(5000);
                $exited = true;
            },
        );
        $reference = WeakReference::create($producer);

        unset($producer);

        self::assertTrue($exited);
        self::assertNull($reference->get());
    }

    public function testInvokableExitCallbackReceivesProducerAndRunsOnce(): void
    {
        $callback = new class {
            public int $calls = 0;

            public int|null $producerId = null;

            public function __invoke(KafkaProducerWrapper $producer): void
            {
                $producer->flushMessages(5000);
                $this->producerId = spl_object_id($producer);
                $this->calls++;
            }
        };
        $producer = new KafkaProducerWrapper(self::createConfig(), $callback);
        $producerId = spl_object_id($producer);
        $reference = WeakReference::create($producer);

        unset($producer);

        self::assertSame(1, $callback->calls);
        self::assertSame($producerId, $callback->producerId);
        self::assertNull($reference->get());
    }

    public function testNativeObserverDoesNotRetainWrapperDuringShutdownFlush(): void
    {
        $config = self::createConfig('127.0.0.1:9092', 10000, 'all');
        $reports = [];
        $config->getConf()->setDrMsgCb(static function (Producer $producer, Message $message) use (&$reports): void {
            $reports[] = $message->err;
        });
        $exited = false;
        $producer = new KafkaProducerWrapper(
            $config,
            static function (KafkaProducerWrapper $producer) use (&$exited): void {
                $producer->flushMessages(5000);
                $exited = true;
            },
        );
        $producer->produce('shutdown-acknowledged-delivery', null, 'payload');
        $wrapperReference = WeakReference::create($producer);
        $nativeReference = WeakReference::create($producer->getProducer());

        // Keep the configuration alive: neither the dispatcher nor its observers may retain the producer.
        unset($producer);

        self::assertTrue($exited);
        self::assertSame([RD_KAFKA_RESP_ERR_NO_ERROR], $reports);
        self::assertNull($wrapperReference->get());
        self::assertNull($nativeReference->get());
    }

    public function testReportedFailureDoesNotInterruptRestOfExitCallback(): void
    {
        $exited = false;
        $producer = new KafkaProducerWrapper(
            self::createConfig(),
            static function (KafkaProducerWrapper $producer) use (&$exited): void {
                $producer->flushMessages(5000);
                $exited = true;
            },
        );
        $producer->produce('already-reported-delivery', null, 'payload');
        $failure = self::flushFailure($producer);
        self::assertSame(-192, $failure->getCode());

        unset($producer);

        self::assertTrue($exited);
    }

    public function testUnobservedShutdownFailureIsHandledByExitCallback(): void
    {
        $failure = null;
        $exited = false;
        $producer = new KafkaProducerWrapper(
            self::createConfig(),
            static function (KafkaProducerWrapper $producer) use (&$failure, &$exited): void {
                try {
                    $producer->flushMessages(5000);
                } catch (DeliveryFailed $exception) {
                    $failure = $exception;
                }

                $exited = true;
            },
        );
        $producer->produce('unobserved-shutdown-delivery', null, 'payload');

        unset($producer);

        self::assertTrue($exited);
        self::assertInstanceOf(DeliveryFailed::class, $failure);
        self::assertSame(-192, $failure->getCode());
        self::assertSame('unobserved-shutdown-delivery', $failure->topicName);
    }

    public function testReportedFailureDoesNotSwallowApplicationExitException(): void
    {
        $expected = new DeliveryFailed('Application exit callback failed');
        $producer = new KafkaProducerWrapper(
            self::createConfig(),
            static function (KafkaProducerWrapper $producer) use ($expected): void {
                $producer->flushMessages(5000);

                throw $expected;
            },
        );
        $producer->produce('reported-before-application-exit', null, 'payload');
        self::flushFailure($producer);

        try {
            unset($producer);
        } catch (DeliveryFailed $exception) {
            self::assertSame($expected, $exception);

            return;
        }

        self::fail('The application exit callback exception was swallowed.');
    }

    private static function createConfig(
        string $broker = '127.0.0.1:1',
        int $messageTimeoutMs = 100,
        string|null $acks = null,
    ): ProducerConfig {
        $config = new ProducerConfig();
        $config->set(ProducerConfig::BOOTSTRAP_SERVERS_CONFIG, $broker);
        $config->set('message.timeout.ms', $messageTimeoutMs);
        $config->set('log_level', 0);
        if ($acks !== null) {
            $config->set(ProducerConfig::ACKS_CONFIG, $acks);
        }

        return $config;
    }

    private static function flushFailure(KafkaProducerWrapper|KafkaProducer $producer): DeliveryFailed
    {
        try {
            $producer->flushMessages(5000);
        } catch (DeliveryFailed $exception) {
            return $exception;
        }

        self::fail('Expected a failed native delivery report to cause DeliveryFailed.');
    }
}
