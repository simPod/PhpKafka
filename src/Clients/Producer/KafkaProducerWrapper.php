<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Producer;

use Closure;
use InvalidArgumentException;
use LogicException;
use RdKafka\Exception as RdKafkaException;
use RdKafka\Message;
use RdKafka\Producer;
use SimPod\Kafka\Clients\Producer\Exception\DeliveryFailed;

use function method_exists;
use function sprintf;

use const RD_KAFKA_PARTITION_UA;
use const RD_KAFKA_RESP_ERR_NO_ERROR;

class KafkaProducerWrapper
{
    private const int RdKafkaMsgFCopy = 0;

    private Producer|null $producer = null;

    private readonly DeliveryFailureState $deliveryFailureState;

    private bool $destructing = false;

    private bool $closed = false;

    private bool $reportsOnlyErrors = false;

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

    /**
     * Native access can bypass enqueue and lifecycle rules. Retained native references remain caller-owned.
     *
     * @throws RdKafkaException
     */
    public function getProducer(): Producer
    {
        $this->assertOpen();
        if ($this->producer !== null) {
            return $this->producer;
        }

        $conf = $this->config->getConf();
        $reportsOnlyErrors = ($conf->dump()['delivery.report.only.error'] ?? 'false') === 'true';
        $producer = new Producer($conf);
        $deliveryFailureState = $this->deliveryFailureState;
        $conf->addDeliveryObserver(
            $producer,
            static function (Producer $producer, Message $message) use ($deliveryFailureState): void {
                $deliveryFailureState->record($message);
            },
        );
        $this->producer = $producer;
        $this->reportsOnlyErrors = $reportsOnlyErrors;

        return $producer;
    }

    /**
     * Exceptions from application callbacks are caller-owned and propagate unchanged.
     *
     * @param TPartition $partition
     * @param array<string, string>|null $headers
     *
     * @throws DeliveryFailed
     * @throws (TPartition is int<min, -1> ? InvalidArgumentException : never) Negative partitions are invalid.
     *
     * @template TPartition of int|null
     */
    public function produce(
        string $topicName,
        int|null $partition,
        string|null $value,
        string|null $key = null,
        array|null $headers = null,
        int|null $timestampMs = null,
    ): void {
        if ($partition < 0) {
            throw new InvalidArgumentException(
                sprintf('Invalid partition: %d. Partition number should always be non-negative or null.', $partition),
            );
        }

        $this->enqueue(new ProducerRecord($topicName, $value, $key, $partition, $headers, $timestampMs));
        $this->poll();
    }

    /**
     * Accept into the native queue without polling application callbacks. Success is not delivery confirmation.
     * Queue-full rejection preserves the native error code and is not retried automatically.
     *
     * @throws DeliveryFailed
     */
    public function enqueue(ProducerRecord $record): void
    {
        $this->assertOpen();
        $this->deliveryFailureState->assertSuccessful();
        if ($record->correlationId !== null && ! method_exists(Producer::class, 'purge')) {
            throw new InvalidArgumentException('This ext-rdkafka build does not support opaque correlation');
        }

        try {
            $producer = $this->getProducer();
            if ($record->correlationId !== null && $this->reportsOnlyErrors) {
                throw new InvalidArgumentException('Opaque correlation requires delivery.report.only.error=false');
            }

            $topic = $producer->newTopic($record->topicName);
            if ($record->correlationId === null) {
                $topic->producev(
                    $record->partition ?? RD_KAFKA_PARTITION_UA,
                    self::RdKafkaMsgFCopy,
                    $record->value,
                    $record->key,
                    $record->headers,
                    $record->timestampMs ?? 0,
                );

                return;
            }

            $topic->producev(
                $record->partition ?? RD_KAFKA_PARTITION_UA,
                self::RdKafkaMsgFCopy,
                $record->value,
                $record->key,
                $record->headers,
                $record->timestampMs ?? 0,
                $record->correlationId,
            );
        } catch (RdKafkaException $exception) {
            throw new DeliveryFailed(
                sprintf('Kafka enqueue failed for topic "%s": %s', $record->topicName, $exception->getMessage()),
                $exception->getCode(),
                $exception,
                $record->topicName,
            );
        }
    }

    /**
     * Serve native callbacks and report delivery failures. Does not initialize an unused wrapper.
     * Application callback exceptions propagate unchanged and do not prove enqueue rejection.
     *
     * @throws DeliveryFailed
     */
    public function poll(int $timeoutMs = 0): void
    {
        $this->assertOpen();
        self::validateTimeout($timeoutMs);
        $this->producer?->poll($timeoutMs);
        $this->deliveryFailureState->assertSuccessful();
    }

    /**
     * Exceptions from application callbacks propagate unchanged.
     *
     * @throws DeliveryFailed
     */
    public function flushMessages(int $timeoutMs = 10000): void
    {
        self::validateTimeout($timeoutMs);
        if ($this->producer === null) {
            return;
        }

        $result = $this->producer->flush($timeoutMs);

        $this->deliveryFailureState->assertSuccessful(suppressReported: $this->destructing);

        if ($result !== RD_KAFKA_RESP_ERR_NO_ERROR) {
            throw new DeliveryFailed('Kafka flush did not complete; pending delivery outcomes remain unknown', $result);
        }
    }

    /**
     * Flush and release this wrapper's native reference. A failed close leaves it open for recovery.
     * Successful close is idempotent and does not initialize an unused wrapper.
     *
     * @throws DeliveryFailed
     */
    public function close(int $timeoutMs = 10000): void
    {
        self::validateTimeout($timeoutMs);
        if ($this->closed) {
            return;
        }

        $this->flushMessages($timeoutMs);
        $this->closed = true;
        $this->producer = null;
    }

    private function assertOpen(): void
    {
        if ($this->closed) {
            throw new LogicException('The producer is closed');
        }
    }

    private static function validateTimeout(int $timeoutMs): void
    {
        if ($timeoutMs < 0) {
            throw new InvalidArgumentException('Timeout must be non-negative');
        }
    }
}
