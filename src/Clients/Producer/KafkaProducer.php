<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Producer;

use InvalidArgumentException;
use RdKafka\Producer;
use RdKafka\ProducerTopic;
use RuntimeException;

use function sprintf;

use const RD_KAFKA_PARTITION_UA;
use const RD_KAFKA_RESP_ERR_NO_ERROR;

class KafkaProducer extends Producer
{
    private const RD_KAFKA_MSG_F_COPY = 0;

    /** @var callable(KafkaProducer):void|null */
    private $exitCallback;

    /** @param callable(KafkaProducer):void|null $exitCallback */
    public function __construct(ProducerConfig $config, callable|null $exitCallback = null)
    {
        $this->exitCallback = $exitCallback;

        parent::__construct($config->getConf());
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

        /** @psalm-var ProducerTopic $topic Psalm thinks this is a Topic https://github.com/vimeo/psalm/issues/3406 */
        $topic = $this->newTopic($topicName);
        $topic->producev(
            $partition ?? RD_KAFKA_PARTITION_UA,
            self::RD_KAFKA_MSG_F_COPY,
            $value,
            $key,
            $headers,
            $timestampMs,
        );
        $this->poll(0);
    }

    public function flushMessages(int $timeoutMs = 10000): void
    {
        $result = null;
        for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
            $result = $this->flush($timeoutMs);
            if ($result === RD_KAFKA_RESP_ERR_NO_ERROR) {
                break;
            }
        }

        if ($result !== RD_KAFKA_RESP_ERR_NO_ERROR) {
            throw new RuntimeException('Was unable to flush, messages might be lost!');
        }
    }
}
