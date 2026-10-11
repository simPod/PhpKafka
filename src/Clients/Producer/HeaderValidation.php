<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Producer;

use InvalidArgumentException;

use function array_keys;
use function is_string;

/** @internal */
final class HeaderValidation
{
    /**
     * Native producev() stops at an integer key and silently drops that header and all subsequent headers.
     * PHP converts numeric string keys such as '123' to integers; do not rename them to bypass this check.
     *
     * @see https://github.com/arnaud-lb/php-rdkafka/blob/268b500da842/topic.c#L548-L551
     * @see https://github.com/arnaud-lb/php-rdkafka/blob/268b500da842/php_rdkafka_priv.h#L135-L146
     *
     * @param array<array-key, string>|null $headers
     *
     * @throws InvalidArgumentException
     *
     * @phpstan-assert array<string, string>|null $headers
     */
    public static function validate(array|null $headers): void
    {
        foreach (array_keys($headers ?? []) as $name) {
            if (! is_string($name)) {
                throw new InvalidArgumentException('Kafka header names must be string array keys.');
            }
        }
    }
}
