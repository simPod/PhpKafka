<?php

declare(strict_types=1);

namespace SimPod\Kafka\Common;

use RdKafka\Conf;

abstract class Config
{
    private Conf $conf;

    public function __construct()
    {
        $this->conf = new Conf();
    }

    public function getConf(): Conf
    {
        return $this->conf;
    }

    /**
     * @param string|int|bool $value
     */
    public function set(string $key, $value): void
    {
        if ($value === true) {
            $value = 'true';
        } elseif ($value === false) {
            $value = 'false';
        } else {
            $value = (string) $value;
        }

        $this->conf->set($key, $value);
    }

    public function get(string $key): string
    {
        return $this->conf->dump()[$key];
    }
}
