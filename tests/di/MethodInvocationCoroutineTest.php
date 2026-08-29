<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Swoole\Coroutine;
use Swoole\Coroutine\WaitGroup;

use function array_values;
use function ksort;
use function Swoole\Coroutine\run;

/**
 * Coroutine isolation of the current MethodInvocation
 *
 * The singleton MethodInvocationProvider is written on every interception.
 * When one coroutine suspends inside an intercepted method while another
 * coroutine's interception begins, each must still resolve its own
 * MethodInvocation, not the other's.
 */
#[RequiresPhpExtension('swoole')]
class MethodInvocationCoroutineTest extends TestCase
{
    public function testMethodInvocationIsIsolatedAcrossCoroutines(): void
    {
        $injector = new Injector(new FakeAssistedDbModule());
        $consumer = $injector->getInstance(FakeAssistedCoroutineConsumer::class);
        $markers = [];
        run(static function () use ($consumer, &$markers): void {
            $wg = new WaitGroup();
            for ($i = 0; $i < 2; $i++) {
                $wg->add();
                Coroutine::create(static function () use ($consumer, &$markers, $wg, $i): void {
                    $invocation = $consumer->currentInvocation($i);
                    /** @var mixed $rawMarker */
                    $rawMarker = $invocation->getArguments()->offsetGet('marker'); // @phpstan-ignore argument.type
                    /** @var int $marker */
                    $marker = $rawMarker;
                    $markers[$i] = $marker;
                    $wg->done();
                });
            }

            $wg->wait();
        });

        ksort($markers);
        $this->assertSame([0, 1], array_values($markers));
    }
}
