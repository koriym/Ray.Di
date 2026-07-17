<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Di\Exception\RenameTargetAlreadyBound;
use Ray\Di\Exception\Unbound;

/**
 * Pins the mechanics of renaming a wrapped module's binding
 *
 * The wrapper transformation itself — move aside, bind over, inject the moved
 * one — is pinned by {@see RenameDecoratorTest}. This covers what a rename does
 * to the index: which name it lands on, which interface, and how it refuses.
 */
class RenameTest extends TestCase
{
    /**
     * With $targetInterface omitted, the rename stays within the same
     * interface -- only the binding name changes.
     */
    public function testRenameWithinSameInterfaceWhenTargetInterfaceOmitted(): void
    {
        $module = new class (new FakeToBindModule()) extends AbstractModule {
            protected function configure(): void
            {
                $this->rename(FakeRobotInterface::class, 'renamed');
            }
        };
        $array = $module->getContainer()->getContainer();

        $this->assertArrayHasKey(FakeRobotInterface::class . '-renamed', $array);
        $this->assertArrayNotHasKey(FakeRobotInterface::class . '-' . Name::ANY, $array);
    }

    /**
     * With $targetInterface specified, the binding moves to a different
     * interface index entirely, not just a new name under the source one.
     */
    public function testMovesToDifferentInterfaceWhenTargetInterfaceSpecified(): void
    {
        $module = new class (new FakeToBindModule()) extends AbstractModule {
            protected function configure(): void
            {
                $this->rename(FakeRobotInterface::class, 'moved', Name::ANY, FakeCarInterface::class);
            }
        };
        $container = $module->getContainer();

        $this->assertInstanceOf(FakeRobot::class, $container->getInstance(FakeCarInterface::class, 'moved'));
        $this->assertArrayNotHasKey(FakeRobotInterface::class . '-' . Name::ANY, $container->getContainer());
    }

    /**
     * Renaming a binding to its own current name is a no-op, not an error.
     * The existing binding remains resolvable under the same name.
     */
    public function testSelfRenameIsNoOp(): void
    {
        $module = new class (new FakeToBindModule()) extends AbstractModule {
            protected function configure(): void
            {
                $this->rename(FakeRobotInterface::class, Name::ANY, Name::ANY);
            }
        };

        $instance = $module->getContainer()->getInstance(FakeRobotInterface::class, Name::ANY);

        $this->assertInstanceOf(FakeRobot::class, $instance);
    }

    /**
     * When the wrapped module has no binding at the source index, rename()
     * surfaces Container::move()'s Unbound rather than silently no-op.
     */
    public function testThrowsUnboundWhenSourceMissing(): void
    {
        $this->expectException(Unbound::class);

        new class (new FakeToBindModule()) extends AbstractModule {
            protected function configure(): void
            {
                $this->rename(FakeRobotInterface::class, 'renamed', 'does-not-exist');
            }
        };
    }

    /**
     * Renaming onto an index that already has a binding is rejected instead of
     * silently overwriting it. The pre-existing binding at the target index
     * remains resolvable afterward, proving move() aborted before mutating the
     * container.
     */
    public function testThrowsWhenTargetAlreadyBoundAndPreservesExistingBinding(): void
    {
        $wrapped = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeRobotInterface::class)->to(FakeRobot::class);
                // occupy the target index before the rename is attempted
                $this->bind(FakeRobotInterface::class)->annotatedWith('renamed')->to(FakeRobot2::class);
            }
        };

        $threw = false;
        try {
            new class ($wrapped) extends AbstractModule {
                protected function configure(): void
                {
                    $this->rename(FakeRobotInterface::class, 'renamed');
                }
            };
        } catch (RenameTargetAlreadyBound $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'RenameTargetAlreadyBound was not thrown');

        // the pre-existing binding at the target index was not destroyed
        $container = $wrapped->getContainer();
        $this->assertInstanceOf(FakeRobot2::class, $container->getInstance(FakeRobotInterface::class, 'renamed'));
        // and the source binding was left untouched since the move aborted
        $this->assertInstanceOf(FakeRobot::class, $container->getInstance(FakeRobotInterface::class, Name::ANY));
    }
}
