<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Clients\Consumer\Exception;

use PHPUnit\Framework\TestCase;
use RdKafka\Message;
use SimPod\Kafka\Clients\Consumer\Exception\IncompatibleStatus;

final class IncompatibleStatusTest extends TestCase
{
    public function testFromMessage(): void
    {
        $message = new Message();
        $message->err = 1;
        $exception = IncompatibleStatus::fromMessage($message);

        self::assertSame(
            'Consumer status "1" is not handled: Broker: Offset out of range',
            $exception->getMessage(),
        );
    }
}
