<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Producer;

use Closure;
use InvalidArgumentException;
use RdKafka\Producer;
use RuntimeException;

use function assert;
use function sprintf;

use const RD_KAFKA_PARTITION_UA;
use const RD_KAFKA_RESP_ERR_NO_ERROR;

final readonly class KafkaProducerWrapper
{
    private const int RdKafkaMsgFCopy = 0;

    public Producer $producer;

    /** @var (Closure(self):void)|null */
    private Closure|null $exitCallback;

    /** @param (Closure(self):void)|null $exitCallback */
    public function __construct(ProducerConfig $config, callable|null $exitCallback = null)
    {
        $this->exitCallback = $exitCallback;
        $this->producer = new Producer($config->getConf());
    }

    public function __destruct()
    {
        if ($this->exitCallback === null) {
            return;
        }

        ($this->exitCallback)($this);
    }

    /** @param array<string, string>|null $headers */
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

        $topic = $this->producer->newTopic($topicName);
        $topic->producev(
            $partition ?? RD_KAFKA_PARTITION_UA,
            self::RdKafkaMsgFCopy,
            $value,
            $key,
            $headers,
            $timestampMs ?? 0,
        );
        $this->producer->poll(0);
    }

    public function flushMessages(int $timeoutMs = 10000): void
    {
        $result = null;
        for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
            $result = $this->producer->flush($timeoutMs);
            if ($result === RD_KAFKA_RESP_ERR_NO_ERROR) {
                break;
            }
        }

        assert($result !== null);

        if ($result !== RD_KAFKA_RESP_ERR_NO_ERROR) {
            throw new RuntimeException('Was unable to flush, messages might be lost!', $result);
        }
    }
}
