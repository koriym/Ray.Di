<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Di\Exception\Unbound;

/**
 * Pins rename() as a wrapper transformation
 *
 * rename() rewires a binding that was made before this module existed: the
 * module wraps another, moves the binding it is about to replace aside, binds
 * its own implementation over it, and injects the moved one by its new name.
 *
 * That shape is the whole reason rename() exists. `BEAR\Package\Context\CliModule`
 * composes every BEAR.Sunday console application with it — the application's
 * RouterInterface moves to 'original' and CliRouter binds over it, injecting the
 * original with #[Named('original')] — and BEAR.Defer (TransferInterface) and
 * BEAR.DevTools (RenderInterface) do the same. A change that flips an assertion
 * here breaks all three; fix the change, not the test.
 *
 * The subject is always the wrapped module's container. That is what makes the
 * ordering rule a single sentence: rename() reaches the past, never the bindings
 * this module is declaring right now. Renaming is therefore a no-op with nothing
 * wrapped, and the source must be found in what was wrapped.
 */
class RenameDecoratorTest extends TestCase
{
    /** The wrapped module's binding is moved aside and replaced, as in CliModule. */
    public function testDecoratesWrappedBinding(): void
    {
        $module = new class (new FakeToBindModule()) extends AbstractModule {
            protected function configure(): void
            {
                $this->rename(FakeRobotInterface::class, 'original');
                $this->bind(FakeRobotInterface::class)
                    ->toConstructor(FakeRobotDecorator::class, ['inner' => 'original']);
            }
        };

        $decorator = $module->getContainer()->getInstance(FakeRobotInterface::class, Name::ANY);

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

    /**
     * A wrapped module without the expected binding is a configuration error,
     * and saying so at construction is the whole value of naming the past: the
     * decorator must never be mistaken for the binding it meant to move.
     */
    public function testMissingSourceInWrappedModuleThrows(): void
    {
        $emptyWrapped = new class extends AbstractModule {
            protected function configure(): void
            {
            }
        };

        $this->expectException(Unbound::class);

        new class ($emptyWrapped) extends AbstractModule {
            protected function configure(): void
            {
                $this->rename(FakeRobotInterface::class, 'original');
                $this->bind(FakeRobotInterface::class)
                    ->toConstructor(FakeRobotDecorator::class, ['inner' => 'original']);
            }
        };
    }

    /**
     * With nothing wrapped there is no past to rewire, so rename() does nothing
     * -- as it has for the whole 2.x line. It must not reach into the bindings
     * this module is declaring, which is how a decorator ends up renamed onto
     * its own inner name and injecting itself.
     */
    public function testRenamingWithNothingWrappedIsNoOp(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new FakeToBindModule());
                $this->rename(FakeRobotInterface::class, 'original');
            }
        };
        $container = $module->getContainer();

        $this->assertInstanceOf(FakeRobot::class, $container->getInstance(FakeRobotInterface::class, Name::ANY));
        $this->assertArrayNotHasKey(FakeRobotInterface::class . '-original', $container->getContainer());
    }
}
