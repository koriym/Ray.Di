<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Di\Exception\Unbound;

/**
 * Pins the rename()-then-rebind decorator idiom
 *
 * A module moves an existing binding aside with rename(), then binds its own
 * implementation to the vacated index and injects the moved one by its new
 * name. That is how a module decorates a binding it does not own.
 *
 * This is not a hypothetical: `BEAR\Package\Context\CliModule` composes the
 * whole CLI context this way -- it renames the application's RouterInterface
 * to 'original' and binds CliRouter over it, which injects the original router
 * with #[Named('original')]. BEAR.Defer (TransferInterface) and BEAR.DevTools
 * (RenderInterface) do the same. A change that flips an assertion here breaks
 * every BEAR.Sunday console application; fix the change, not the test.
 *
 * The pinned order is deliberate. rename() is called while configure() runs,
 * before the constructor-chained module that owns the source binding has been
 * merged, so a decorator must be able to name a binding that has not composed
 * yet.
 */
class RenameDecoratorTest extends TestCase
{
    /**
     * The source binding arrives with the constructor-chained module, as an
     * application module hands RouterInterface to CliModule.
     */
    public function testDecoratesConstructorChainedBinding(): void
    {
        $module = new class (new FakeToBindModule()) extends AbstractModule {
            protected function configure(): void
            {
                $this->rename(FakeRobotInterface::class, 'original');
                $this->bind(FakeRobotInterface::class)
                    ->toConstructor(FakeRobotDecorator::class, ['inner' => 'original']);
            }
        };
        $container = $module->getContainer();

        $decorator = $container->getInstance(FakeRobotInterface::class, Name::ANY);
        $this->assertInstanceOf(FakeRobotDecorator::class, $decorator);
        $this->assertInstanceOf(FakeRobot::class, $decorator->inner);
    }

    /** The moved binding stays reachable under its new name, as the decorator injects it. */
    public function testMovedBindingIsInjectableByItsNewName(): void
    {
        $module = new class (new FakeToBindModule()) extends AbstractModule {
            protected function configure(): void
            {
                $this->rename(FakeRobotInterface::class, 'original');
                $this->bind(FakeRobotInterface::class)
                    ->toConstructor(FakeRobotDecorator::class, ['inner' => 'original']);
            }
        };

        $original = $module->getContainer()->getInstance(FakeRobotInterface::class, 'original');

        $this->assertInstanceOf(FakeRobot::class, $original);
    }

    /** The same idiom against an already-composed source: install() has merged the binding. */
    public function testDecoratesInstalledBinding(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new FakeToBindModule());
                $this->rename(FakeRobotInterface::class, 'original');
                $this->bind(FakeRobotInterface::class)
                    ->toConstructor(FakeRobotDecorator::class, ['inner' => 'original']);
            }
        };

        $decorator = $module->getContainer()->getInstance(FakeRobotInterface::class, Name::ANY);

        $this->assertInstanceOf(FakeRobotDecorator::class, $decorator);
        $this->assertInstanceOf(FakeRobot::class, $decorator->inner);
    }

    /**
     * When the chained module fails to provide the source -- an application
     * without a RouterInterface handed to CliModule -- construction must fail
     * loudly, as moving on the chained container did in 2.20. The pending
     * rename must never fall through to the module's own container, where it
     * would move the decorator onto the source's new name: the decorator then
     * injects itself, and the unnamed index resolves to nothing.
     */
    public function testMissingSourceInChainedModuleFailsAtConstruction(): void
    {
        $emptyChained = new class extends AbstractModule {
            protected function configure(): void
            {
            }
        };

        $this->expectException(Unbound::class);

        new class ($emptyChained) extends AbstractModule {
            protected function configure(): void
            {
                $this->rename(FakeRobotInterface::class, 'original');
                $this->bind(FakeRobotInterface::class)
                    ->toConstructor(FakeRobotDecorator::class, ['inner' => 'original']);
            }
        };
    }
}
