<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Aop\Matcher;

class AbstractModuleTest extends TestCase
{
    /**
     * override() merges THIS module's bindings into the target module and then
     * adopts that merged container. Both modules' bindings must resolve, and in
     * particular this module's own binding must survive the merge. If the merge
     * step is skipped, this module's binding is lost.
     *
     * @covers \Ray\Di\AbstractModule::override
     */
    public function testOverrideMergesThisModuleIntoTarget(): void
    {
        $base = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeRobotInterface::class)->to(FakeRobot::class);
            }
        };
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeEngineInterface::class)->toInstance(new FakeEngine());
            }
        };

        $module->override($base);
        $container = $module->getContainer();

        // the target module's binding is visible through the merged container
        $this->assertInstanceOf(FakeRobot::class, $container->getInstance(FakeRobotInterface::class, Name::ANY));
        // and this module's own binding survived the merge
        $this->assertInstanceOf(FakeEngine::class, $container->getInstance(FakeEngineInterface::class, Name::ANY));
    }

    /**
     * bindInterceptor() registers each interceptor in the container as a
     * singleton so the woven proxy can resolve it.
     *
     * @covers \Ray\Di\AbstractModule::bindInterceptor
     */
    public function testBindInterceptorRegistersInterceptor(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
            }
        };
        $module->bindInterceptor((new Matcher())->any(), (new Matcher())->any(), [FakeDoubleInterceptor::class]);

        $interceptor = $module->getContainer()->getInstance(FakeDoubleInterceptor::class, Name::ANY);
        $this->assertInstanceOf(FakeDoubleInterceptor::class, $interceptor);
    }

    /**
     * bindInterceptor() must register EVERY interceptor in the list, not just
     * the first one. If the loop short-circuits (returns) after binding the
     * first class interceptor, the second one is never registered and resolving
     * it throws Unbound.
     *
     * @covers \Ray\Di\AbstractModule::bindInterceptor
     */
    public function testBindInterceptorRegistersAllInterceptors(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
            }
        };
        $module->bindInterceptor((new Matcher())->any(), (new Matcher())->any(), [FakeDoubleInterceptor::class, FakeAnnoInterceptor1::class]);

        $container = $module->getContainer();
        $this->assertInstanceOf(FakeDoubleInterceptor::class, $container->getInstance(FakeDoubleInterceptor::class, Name::ANY));
        $this->assertInstanceOf(FakeAnnoInterceptor1::class, $container->getInstance(FakeAnnoInterceptor1::class, Name::ANY));
    }

    /**
     * bindPriorityInterceptor() likewise registers each interceptor as a
     * singleton in the container.
     *
     * @covers \Ray\Di\AbstractModule::bindPriorityInterceptor
     */
    public function testBindPriorityInterceptorRegistersInterceptor(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
            }
        };
        $module->bindPriorityInterceptor((new Matcher())->any(), (new Matcher())->any(), [FakeDoubleInterceptor::class]);

        $interceptor = $module->getContainer()->getInstance(FakeDoubleInterceptor::class, Name::ANY);
        $this->assertInstanceOf(FakeDoubleInterceptor::class, $interceptor);
    }
}
