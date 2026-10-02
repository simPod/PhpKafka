<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Producer;

use Closure;
use RdKafka\Conf;
use RdKafka\Message;
use RdKafka\Producer;
use WeakMap;

final class ProducerConf extends Conf
{
    /** @var WeakMap<Producer, list<Closure(Producer, Message): void>> */
    private readonly WeakMap $deliveryObservers;

    public function __construct()
    {
        parent::__construct();

        $this->deliveryObservers = new WeakMap();
        $this->installDispatcher(null);
    }

    /** @param callable(Producer, Message): void $callback */
    public function setDrMsgCb(callable $callback): void
    {
        $this->installDispatcher(Closure::fromCallable($callback));
    }

    /**
     * @internal Observers must not retain a producer or its configuration.
     *
     * @param Closure(Producer, Message): void $observer
     */
    public function addDeliveryObserver(Producer $producer, Closure $observer): void
    {
        $observers = $this->deliveryObservers[$producer] ?? [];
        $observers[] = $observer;
        $this->deliveryObservers[$producer] = $observers;
    }

    /** @param (Closure(Producer, Message): void)|null $callback */
    private function installDispatcher(Closure|null $callback): void
    {
        $deliveryObservers = $this->deliveryObservers;

        parent::setDrMsgCb(static function (
            Producer $producer,
            Message $message,
        ) use (
            $deliveryObservers,
            $callback,
        ): void {
            foreach ($deliveryObservers[$producer] ?? [] as $observer) {
                $observer($producer, $message);
            }

            if ($callback !== null) {
                $callback($producer, $message);
            }
        });
    }
}
