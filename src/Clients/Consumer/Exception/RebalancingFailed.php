<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Consumer\Exception;

use SimPod\Kafka\Common\Exception\KafkaException;
use function rd_kafka_err2str;
use function sprintf;

final class RebalancingFailed extends KafkaException
{
    public static function new(int $err) : self
    {
        return new self(sprintf('Rebalancing failed: %s (%d)', rd_kafka_err2str($err), $err));
    }
}
