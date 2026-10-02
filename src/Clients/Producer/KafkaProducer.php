<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Producer;

use InvalidArgumentException;
use RdKafka\Exception as RdKafkaException;
use RdKafka\Message;
use RdKafka\Producer;
use RdKafka\ProducerTopic;
use SimPod\Kafka\Clients\Producer\Exception\DeliveryFailed;

use function assert;
use function sprintf;

use const RD_KAFKA_PARTITION_UA;
use const RD_KAFKA_RESP_ERR_NO_ERROR;

/** @deprecated Use {@see \SimPod\Kafka\Clients\Producer\KafkaProducerWrapper} instead */
class KafkaProducer extends Producer
{
    // phpcs:disable Cdn77.NamingConventions.ValidConstantName.ClassConstantNotUpperCase
    private const int RD_KAFKA_MSG_F_COPY = 0;

    private readonly DeliveryFailureState $deliveryFailureState;

    private bool $destructing = false;

    /** @var callable(KafkaProducer):void|null */
    private $exitCallback;

    /**
     * @param callable(KafkaProducer):void|null $exitCallback
     *
     * @throws RdKafkaException
     */
    public function __construct(ProducerConfig $config, callable|null $exitCallback = null)
    {
        $this->exitCallback = $exitCallback;
        $this->deliveryFailureState = new DeliveryFailureState();

        $conf = $config->getConf();
        parent::__construct($conf);

        $deliveryFailureState = $this->deliveryFailureState;
        $conf->addDeliveryObserver(
            $this,
            static function (Producer $producer, Message $message) use ($deliveryFailureState): void {
                $deliveryFailureState->record($message);
            },
        );
    }

    /** Exceptions from application callbacks propagate unchanged. */
    public function __destruct()
    {
        if ($this->exitCallback === null) {
            return;
        }

        $this->destructing = true;
        ($this->exitCallback)($this);
    }

    /**
     * Exceptions from application callbacks propagate unchanged.
     *
     * @param array<string, string>|null $headers
     *
     * @throws DeliveryFailed
     * @throws InvalidArgumentException
     */
    public function produce(
        string $topicName,
        int|null $partition,
        string $value,
        string|null $key = null,
        array|null $headers = null,
        int|null $timestampMs = null,
    ): void {
        if ($partition < 0) {
            throw new InvalidArgumentException(
                sprintf('Invalid partition: %d. Partition number should always be non-negative or null.', $partition),
            );
        }

        $this->deliveryFailureState->assertSuccessful();

        try {
            // Psalm sees Topic instead: https://github.com/vimeo/psalm/issues/3406
            /** @phpstan-var ProducerTopic $topic */
            $topic = $this->newTopic($topicName);
            $topic->producev(
                $partition ?? RD_KAFKA_PARTITION_UA,
                self::RD_KAFKA_MSG_F_COPY,
                $value,
                $key,
                $headers,
                $timestampMs ?? 0,
            );
        } catch (RdKafkaException $exception) {
            throw new DeliveryFailed(
                sprintf('Kafka enqueue failed for topic "%s": %s', $topicName, $exception->getMessage()),
                $exception->getCode(),
                $exception,
                $topicName,
            );
        }

        $this->poll(0);
        $this->deliveryFailureState->assertSuccessful();
    }

    /**
     * Exceptions from application callbacks propagate unchanged.
     *
     * @throws DeliveryFailed
     */
    public function flushMessages(int $timeoutMs = 10000): void
    {
        $result = null;
        for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
            $result = $this->flush($timeoutMs);
            if ($result === RD_KAFKA_RESP_ERR_NO_ERROR) {
                break;
            }
        }

        assert($result !== null);

        $this->deliveryFailureState->assertSuccessful(suppressReported: $this->destructing);

        if ($result !== RD_KAFKA_RESP_ERR_NO_ERROR) {
            throw new DeliveryFailed('Was unable to flush, messages might be lost!', $result);
        }
    }
}
