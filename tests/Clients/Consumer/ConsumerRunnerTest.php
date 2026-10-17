<?php

declare(strict_types=1);

namespace SimPod\Kafka\Tests\Clients\Consumer;

use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RdKafka\Conf;
use SimPod\Kafka\Clients\Consumer\ConsumerRunner;
use WeakReference;

use function gc_collect_cycles;

final class ConsumerRunnerTest extends TestCase
{
    public function testExplicitCloseAllowsRunnerCollection(): void
    {
        $runner = new ConsumerRunner(static function (Conf $config): void {
            $config->set('group.id', 'runner-collection');
            $config->set('enable.auto.commit', 'false');
            $config->set('log_level', '0');
        });
        $reference = WeakReference::create($runner);

        $runner->close();
        unset($runner);
        gc_collect_cycles();

        self::assertNull($reference->get(), 'The native rebalance callback must not retain the runner');
    }

    public function testNativeAutoCommitMustBeDisabled(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('enable.auto.commit=false');

        new ConsumerRunner(static function (Conf $config): void {
            $config->set('group.id', 'unsafe-auto-commit');
        });
    }

    #[DataProvider('provideIncompatibleStrategies')]
    public function testIncompatibleStrategies(string $strategy): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ConsumerRunner(static function (Conf $config) use ($strategy): void {
            $config->set('group.id', 'incompatible-strategy');
            $config->set('enable.auto.commit', 'false');
            $config->set('partition.assignment.strategy', $strategy);
        });
    }

    /** @phpstan-return Generator<string, array{string}> */
    public static function provideIncompatibleStrategies(): Generator
    {
        yield 'mixed protocols' => ['range,cooperative-sticky'];
        yield 'multiple cooperative entries' => ['cooperative-sticky,cooperative-sticky'];
    }
}
