<?php

declare(strict_types=1);

namespace SimPod\Kafka\Clients\Consumer;

use SimPod\Kafka\Common\Exception\Wakeup;
use function pcntl_signal;
use function pcntl_sigprocmask;
use const SIG_BLOCK;
use const SIG_DFL;
use const SIG_UNBLOCK;
use const SIGHUP;
use const SIGINT;
use const SIGIO;
use const SIGTERM;

trait WithSignalControl
{
    private function setupInternalTerminationSignal(ConsumerConfig $config) : void
    {
        $config->set('internal.termination.signal', SIGIO);
    }

    private function registerSignals() : void
    {
        $terminationCallback = static function () : void {
            throw new Wakeup();
        };

        pcntl_signal(SIGTERM, $terminationCallback);
        pcntl_signal(SIGINT, $terminationCallback);
        pcntl_signal(SIGHUP, $terminationCallback);
        pcntl_sigprocmask(SIG_BLOCK, [SIGIO]);
    }

    private function degisterSignals() : void
    {
        pcntl_signal(SIGTERM, SIG_DFL);
        pcntl_signal(SIGINT, SIG_DFL);
        pcntl_signal(SIGHUP, SIG_DFL);
        pcntl_sigprocmask(SIG_UNBLOCK, [SIGIO]);
    }
}
