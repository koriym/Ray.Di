<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Di\Exception\RenameTargetAlreadyBound;
use Ray\Di\Exception\Unbound;

class RenameTest extends TestCase
{
    /**
     * rename() must operate on getContainer() directly, so a binding
     * introduced via install() -- not just constructor chaining -- can be
     * renamed. After the move, the new name resolves and the old (unnamed)
     * index is gone.
     */
    public function testRenamesBindingIntroducedByInstall(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new FakeToBindModule());
                $this->rename(FakeRobotInterface::class, 'renamed');
            }
        };
        $container = $module->getContainer();

        $instance = $container->getInstance(FakeRobotInterface::class, 'renamed');
        $this->assertInstanceOf(FakeRobot::class, $instance);

        $this->expectException(Unbound::class);
        $container->getInstance(FakeRobotInterface::class, Name::ANY);
    }

    /**
     * override() replaces $this->container with the target module's (merged)
     * container. rename() inside configure() is deferred and applied against
     * getContainer() after composition completes, so it must act on that
     * merged container, not on a stale reference captured before the swap.
     */
    public function testRenamesBindingAfterOverride(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new FakeToBindModule());
                $this->override(new class extends AbstractModule {
                    protected function configure(): void
                    {
                        $this->bind(FakeEngineInterface::class)->toInstance(new FakeEngine());
                    }
                });
                $this->rename(FakeRobotInterface::class, 'renamed');
            }
        };
        $container = $module->getContainer();

        // the renamed binding, originally introduced before override(), is reachable
        $instance = $container->getInstance(FakeRobotInterface::class, 'renamed');
        $this->assertInstanceOf(FakeRobot::class, $instance);

        // and the override target's own binding survived alongside it
        $this->assertInstanceOf(FakeEngine::class, $container->getInstance(FakeEngineInterface::class, Name::ANY));
    }

    /**
     * A deferred rename whose source never arrives -- not with the chained
     * module either -- must still surface Unbound, not vanish.
     */
    public function testThrowsUnboundWhenDeferredSourceNeverArrives(): void
    {
        $this->expectException(Unbound::class);

        new class (new FakeToBindModule()) extends AbstractModule {
            protected function configure(): void
            {
                // FakeToBindModule binds FakeRobotInterface, never FakeEngineInterface
                $this->rename(FakeEngineInterface::class, 'renamed');
            }
        };
    }

    /**
     * When configure() has bound the index a deferred rename targets, the
     * rename must be rejected rather than lose that binding to the merge.
     */
    public function testThrowsWhenDeferredRenameTargetIsAlreadyBound(): void
    {
        $this->expectException(RenameTargetAlreadyBound::class);

        new class (new FakeToBindModule()) extends AbstractModule {
            protected function configure(): void
            {
                $this->rename(FakeRobotInterface::class, 'renamed');
                $this->bind(FakeRobotInterface::class)->annotatedWith('renamed')->to(FakeRobot2::class);
            }
        };
    }

    /**
     * When no binding exists at the source index, rename() must
     * surface Container::move()'s Unbound rather than silently no-op.
     */
    public function testThrowsUnboundWhenSourceMissing(): void
    {
        $this->expectException(Unbound::class);

        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new FakeToBindModule());
                $this->rename(FakeRobotInterface::class, 'renamed', 'does-not-exist');
            }
        };
    }

    /**
     * Renaming onto an index that already has a binding must be rejected
     * instead of silently overwriting it. The pre-existing binding at the
     * target index must remain resolvable afterward, proving move() aborted
     * before mutating the container.
     */
    public function testThrowsWhenTargetAlreadyBoundAndPreservesExistingBinding(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new FakeToBindModule());
                // occupy the target index before attempting the rename
                $this->bind(FakeRobotInterface::class)->annotatedWith('renamed')->to(FakeRobot2::class);
            }
        };

        $threw = false;
        try {
            $module->rename(FakeRobotInterface::class, 'renamed');
        } catch (RenameTargetAlreadyBound $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'RenameTargetAlreadyBound was not thrown');

        // the pre-existing binding at the target index was not destroyed
        $instance = $module->getContainer()->getInstance(FakeRobotInterface::class, 'renamed');
        $this->assertInstanceOf(FakeRobot2::class, $instance);

        // and the source binding was left untouched since the move aborted
        $source = $module->getContainer()->getInstance(FakeRobotInterface::class, Name::ANY);
        $this->assertInstanceOf(FakeRobot::class, $source);
    }

    /**
     * With $targetInterface omitted, the rename must stay within the same
     * interface -- only the binding name changes.
     */
    public function testRenameWithinSameInterfaceWhenTargetInterfaceOmitted(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new FakeToBindModule());
                $this->rename(FakeRobotInterface::class, 'renamed');
            }
        };
        $container = $module->getContainer();
        $array = $container->getContainer();

        $this->assertArrayHasKey(FakeRobotInterface::class . '-renamed', $array);
        $this->assertArrayNotHasKey(FakeRobotInterface::class . '-' . Name::ANY, $array);
    }

    /**
     * With $targetInterface specified, the binding must move to a different
     * interface index entirely, not just get a new name under the source
     * interface.
     */
    public function testMovesToDifferentInterfaceWhenTargetInterfaceSpecified(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new FakeToBindModule());
                $this->rename(FakeRobotInterface::class, 'moved', Name::ANY, FakeCarInterface::class);
            }
        };
        $container = $module->getContainer();
        $array = $container->getContainer();

        $this->assertArrayHasKey(FakeCarInterface::class . '-moved', $array);
        $this->assertArrayNotHasKey(FakeRobotInterface::class . '-' . Name::ANY, $array);

        $instance = $container->getInstance(FakeCarInterface::class, 'moved');
        $this->assertInstanceOf(FakeRobot::class, $instance);
    }

    /**
     * Renaming a binding to its own current name must be a no-op, not an
     * error. The existing binding must remain resolvable under the same name.
     */
    public function testSelfRenameIsNoOp(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new FakeToBindModule());
                $this->rename(FakeRobotInterface::class, Name::ANY, Name::ANY);
            }
        };
        $container = $module->getContainer();

        $instance = $container->getInstance(FakeRobotInterface::class, Name::ANY);
        $this->assertInstanceOf(FakeRobot::class, $instance);
    }
}
