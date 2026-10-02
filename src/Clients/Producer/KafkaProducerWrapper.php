<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Producer;

use Closure;
use InvalidArgumentException;
use RdKafka\Exception as RdKafkaException;
use RdKafka\Message;
use RdKafka\Producer;
use SimPod\Kafka\Clients\Producer\Exception\DeliveryFailed;

use function assert;
use function sprintf;

use const RD_KAFKA_PARTITION_UA;
use const RD_KAFKA_RESP_ERR_NO_ERROR;

class KafkaProducerWrapper
{
    private const int RdKafkaMsgFCopy = 0;

    private Producer|null $producer = null;

    private readonly DeliveryFailureState $deliveryFailureState;

    private bool $destructing = false;

    /** @var (Closure(self):void)|null */
    private readonly Closure|null $exitCallback;

    /** @param (callable(self):void)|null $exitCallback */
    public function __construct(
        private readonly ProducerConfig $config,
        callable|null $exitCallback = null,
    ) {
        $this->deliveryFailureState = new DeliveryFailureState();
        $this->exitCallback = $exitCallback === null ? null : Closure::fromCallable($exitCallback);
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

    /** @throws RdKafkaException */
    public function getProducer(): Producer
    {
        if ($this->producer !== null) {
            return $this->producer;
        }

        $conf = $this->config->getConf();
        $producer = new Producer($conf);
        $deliveryFailureState = $this->deliveryFailureState;
        $conf->addDeliveryObserver(
            $producer,
            static function (Producer $producer, Message $message) use ($deliveryFailureState): void {
                $deliveryFailureState->record($message);
            },
        );
        $this->producer = $producer;

        return $producer;
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
            $producer = $this->getProducer();
            $topic = $producer->newTopic($topicName);
            $topic->producev(
                $partition ?? RD_KAFKA_PARTITION_UA,
                self::RdKafkaMsgFCopy,
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

        $producer->poll(0);
        $this->deliveryFailureState->assertSuccessful();
    }

    /**
     * Exceptions from application callbacks propagate unchanged.
     *
     * @throws DeliveryFailed
     */
    public function flushMessages(int $timeoutMs = 10000): void
    {
        if ($this->producer === null) {
            return;
        }

        $result = null;
        for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
            $result = $this->producer->flush($timeoutMs);
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
