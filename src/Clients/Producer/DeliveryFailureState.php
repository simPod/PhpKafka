<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Producer;

use RdKafka\Message;
use SimPod\Kafka\Clients\Producer\Exception\DeliveryFailed;

use function sprintf;

use const RD_KAFKA_RESP_ERR_NO_ERROR;

/** @internal */
final class DeliveryFailureState
{
    public private(set) bool $reported = false;

    private int|null $errorCode = null;

    private string $errorMessage = '';

    private string|null $topicName = null;

    public function record(Message $message): void
    {
        if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR || $this->errorCode !== null) {
            return;
        }

        $this->errorCode = $message->err;
        $this->errorMessage = $message->errstr() ?? 'Unknown delivery error';
        // phpcs:ignore Cdn77.NamingConventions.ValidVariableName -- Native RdKafka field name.
        $this->topicName = $message->topic_name;
    }

    /** @throws DeliveryFailed */
    public function assertSuccessful(bool $suppressReported = false): void
    {
        if ($this->errorCode === null || ($suppressReported && $this->reported)) {
            return;
        }

        $this->reported = true;

        throw new DeliveryFailed(
            sprintf('Kafka delivery failed for topic "%s": %s', $this->topicName ?? '', $this->errorMessage),
            $this->errorCode,
            topicName: $this->topicName,
        );
    }
}
