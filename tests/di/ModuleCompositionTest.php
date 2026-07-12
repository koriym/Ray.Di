<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;

use function assert;

/**
 * Pins the composition contract for constructor-chained modules
 *
 * `new OuterModule(new InnerModule())` composes with outer-module priority:
 * everything OuterModule::configure() declares — directly via bind(), via
 * install(), and via bindInterceptor() — takes precedence over (or wraps)
 * what the chained InnerModule declared. BEAR.Sunday context modules
 * (e.g. `new ProdModule(new HalModule(new AppModule()))`) rely on this
 * contract: the left (outer) context module must override the right one.
 */
class ModuleCompositionTest extends TestCase
{
    /**
     * A binding introduced by install() inside configure() must override the
     * same binding coming from the constructor-chained module. ProdModule-style
     * context modules bind exclusively through install(), so if the chained
     * module won here, a prod context would silently run with dev bindings.
     *
     * @covers \Ray\Di\AbstractModule::__construct
     * @covers \Ray\Di\AbstractModule::install
     */
    public function testInstallOverridesConstructorChainedBinding(): void
    {
        $module = new class (new FakeToBindModule()) extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new class extends AbstractModule {
                    protected function configure(): void
                    {
                        $this->bind(FakeRobotInterface::class)->to(FakeRobot2::class);
                    }
                });
            }
        };

        $instance = $module->getContainer()->getInstance(FakeRobotInterface::class, Name::ANY);

        $this->assertInstanceOf(FakeRobot2::class, $instance);
    }

    /**
     * Re-declaring an untargeted binding to change its scope must override the
     * constructor-chained module's binding. `bind(Concrete::class)->in(SINGLETON)`
     * is the documented idiom to singletonize a concrete class; it must not be
     * silently ignored just because the chained module already bound the class.
     *
     * @covers \Ray\Di\AbstractModule::__construct
     * @covers \Ray\Di\AbstractModule::bind
     */
    public function testRebindScopeOverridesConstructorChainedBinding(): void
    {
        $chained = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeEngine::class); // untargeted, prototype
            }
        };
        $module = new class ($chained) extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeEngine::class)->in(Scope::SINGLETON);
            }
        };
        $injector = new Injector($module);

        $this->assertSame(
            $injector->getInstance(FakeEngine::class),
            $injector->getInstance(FakeEngine::class)
        );
    }

    /**
     * The module's own interceptor must wrap the constructor-chained module's
     * interceptor when both pointcuts match the same method. With the own
     * FakeDoubleInterceptor outermost, returnSame(2) is (2 + 1) * 2 = 6;
     * if the chained FakeIncrementInterceptor wrapped it instead, the result
     * would be (2 * 2) + 1 = 5.
     *
     * @covers \Ray\Di\AbstractModule::__construct
     * @covers \Ray\Di\AbstractModule::bindInterceptor
     */
    public function testOwnInterceptorWrapsConstructorChainedInterceptor(): void
    {
        $chained = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bindInterceptor(
                    $this->matcher->any(),
                    $this->matcher->any(),
                    [FakeIncrementInterceptor::class]
                );
            }
        };
        $module = new class ($chained) extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeAopInterface::class)->to(FakeAop::class);
                $this->bindInterceptor(
                    $this->matcher->any(),
                    $this->matcher->any(),
                    [FakeDoubleInterceptor::class]
                );
            }
        };
        $instance = (new Injector($module))->getInstance(FakeAopInterface::class);
        assert($instance instanceof FakeAop);

        $this->assertSame(6, $instance->returnSame(2));
    }
}
