<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Aop\Compiler;
use Ray\Aop\Matcher;
use Ray\Aop\Pointcut;
use Ray\Aop\WeavedInterface;
use ReflectionClass;
use ReflectionMethod;

use function assert;
use function is_object;
use function property_exists;
use function serialize;
use function spl_object_hash;
use function unserialize;

class DependencyTest extends TestCase
{
    /** @var Dependency */
    private $dependency;

    protected function setUp(): void
    {
        /** @var ReflectionClass<object> $class */
        $class = new ReflectionClass(FakeCar::class);
        $setters = [];
        $name = new Name(Name::ANY);
        $setters[] = new SetterMethod(new ReflectionMethod(FakeCar::class, 'setTires'), $name);
        $setters[] = new SetterMethod(new ReflectionMethod(FakeCar::class, 'setHardtop'), $name);
        $setterMethods = new SetterMethods($setters);
        $newInstance = new NewInstance($class, $setterMethods);
        $this->dependency = new Dependency($newInstance, new ReflectionMethod(FakeCar::class, 'postConstruct'));
    }

    /**
     * @return Container[][]
     * @psalm-return array{0: array{0: Container}}
     */
    public function containerProvider(): array
    {
        $container = new Container();
        (new Bind($container, FakeTyreInterface::class))->to(FakeTyre::class);
        (new Bind($container, FakeEngineInterface::class))->to(FakeEngine::class);
        (new Bind($container, FakeHardtopInterface::class))->to(FakeHardtop::class);

        return [[$container]];
    }

    /**
     * __toString() is the human-readable diagnostic form of a binding.
     * ModuleString/ModuleJson extract targets structurally via
     * BindingTargetVisitor, so this contract is pinned here directly.
     */
    public function testToStringShowsDependencyClass(): void
    {
        $this->assertSame('(dependency) ' . FakeCar::class, (string) $this->dependency);
    }

    /**
     * @dataProvider containerProvider
     */
    public function testInject(Container $container): void
    {
        $car = $this->dependency->inject($container);
        /** @var FakeCar $car */
        $this->assertInstanceOf(FakeCar::class, $car);
    }

    /**
     * @dataProvider containerProvider
     */
    public function testSetterInjection(Container $container): void
    {
        $car = $this->dependency->inject($container);
        /** @var FakeCar $car */
        $this->assertInstanceOf(FakeCar::class, $car);
        $this->assertInstanceOf(FakeTyre::class, $car->frontTyre);
    }

    /**
     * @dataProvider containerProvider
     */
    public function testPostConstruct(Container $container): void
    {
        $car = $this->dependency->inject($container);
        /** @var FakeCar $car */
        $this->assertTrue($car->isConstructed);
    }

    /**
     * @dataProvider containerProvider
     */
    public function testPrototype(Container $container): void
    {
        $this->dependency->setScope(Scope::PROTOTYPE);
        $car1 = $this->dependency->inject($container);
        $car2 = $this->dependency->inject($container);
        assert(is_object($car1) && is_object($car2));
        $this->assertNotSame(spl_object_hash($car1), spl_object_hash($car2));
    }

    /**
     * @dataProvider containerProvider
     */
    public function testSingleton(Container $container): void
    {
        $this->dependency->setScope(Scope::SINGLETON);
        $car1 = $this->dependency->inject($container);
        $car2 = $this->dependency->inject($container);
        assert(is_object($car1) && is_object($car2));
        $this->assertSame(spl_object_hash($car1), spl_object_hash($car2));
    }

    /**
     * A singleton must resolve to a fresh instance after the very first
     * inject() call following unserialize(), because $isInstantiated is not
     * listed in __sleep() and therefore resets to false. From then on, the
     * unserialized instance must cache and return the same object like any
     * other singleton. Tracking instantiation through `$instance !== null`
     * instead of an explicit flag would behave the same way here (the
     * instance is also dropped by __sleep()), but this test pins the
     * DependencyProvider-aligned $isInstantiated behaviour explicitly.
     *
     * @covers \Ray\Di\Dependency::inject
     */
    public function testSingletonAfterUnserializeReinstantiatesOnceThenCaches(): void
    {
        /** @var ReflectionClass<object> $class */
        $class = new ReflectionClass(FakeAop::class);
        $dependency = new Dependency(new NewInstance($class, new SetterMethods([])));
        $dependency->setScope(Scope::SINGLETON);
        $container = new Container();

        $before = $dependency->inject($container);

        $serialized = serialize($dependency);
        $unserialized = unserialize($serialized);
        assert($unserialized instanceof Dependency);

        $first = $unserialized->inject($container);
        $second = $unserialized->inject($container);

        assert(is_object($before) && is_object($first) && is_object($second));
        // The unserialized dependency lost its cached instance, so it must
        // build a new one rather than resurrecting the pre-serialization object.
        $this->assertNotSame(spl_object_hash($before), spl_object_hash($first));
        // Once rebuilt, the instance must be cached like a normal singleton.
        $this->assertSame(spl_object_hash($first), spl_object_hash($second));
    }

    public function testInjectInterceptor(): void
    {
        /** @var ReflectionClass<object> $class */
        $class = new ReflectionClass(FakeAop::class);
        $dependency = new Dependency(new NewInstance($class, new SetterMethods([])));
        $pointcut = new Pointcut((new Matcher())->any(), (new Matcher())->any(), [FakeDoubleInterceptor::class]);
        $dependency->weaveAspects(new Compiler(__DIR__ . '/tmp'), [$pointcut]);
        $container = new Container();
        $container->add((new Bind($container, FakeDoubleInterceptor::class))->to(FakeDoubleInterceptor::class));
        $instance = $dependency->inject($container);
        assert(is_object($instance));
        $isWeave = (new ReflectionClass($instance))->implementsInterface(WeavedInterface::class);
        $this->assertTrue($isWeave);
        assert(property_exists($instance, 'bindings'));
        $this->assertArrayHasKey('returnSame', (array) $instance->bindings);
    }

    /**
     * @PostConstruct must run on the assisted-injection (args) path.
     *
     * FakeCar::postConstruct() sets isConstructed = true only when both the
     * constructor-injected engine and the setter-injected front tyre are present.
     * If the postConstruct call in injectWithArgs() is removed, isConstructed
     * stays false.
     * @dataProvider containerProvider
     * @covers \Ray\Di\Dependency::injectWithArgs
     */
    public function testInjectWithArgsPostConstruct(Container $container): void
    {
        $car = $this->dependency->injectWithArgs($container, [new FakeEngine()]);
        assert($car instanceof FakeCar);
        $this->assertInstanceOf(FakeCar::class, $car);
        $this->assertTrue($car->isConstructed);
    }

    /**
     * The singleton fast-path of injectWithArgs() must return the very same
     * instance on repeated calls. If the early `return $this->instance` is
     * removed, a fresh instance is built every time and the two differ.
     *
     * @dataProvider containerProvider
     * @covers \Ray\Di\Dependency::injectWithArgs
     */
    public function testInjectWithArgsSingleton(Container $container): void
    {
        $this->dependency->setScope(Scope::SINGLETON);
        $car1 = $this->dependency->injectWithArgs($container, [new FakeEngine()]);
        $car2 = $this->dependency->injectWithArgs($container, [new FakeEngine()]);
        assert(is_object($car1) && is_object($car2));
        $this->assertInstanceOf(FakeCar::class, $car2);
        $this->assertSame($car1, $car2);
    }

    /**
     * @PostConstruct must run exactly once for a singleton on the args path,
     * even though injectWithArgs() is called repeatedly. The recorded
     * postConstruct invocation count proves the cached instance short-circuits
     * before postConstruct runs again.
     * @covers \Ray\Di\Dependency::injectWithArgs
     */
    public function testInjectWithArgsSingletonPostConstructRunsOnce(): void
    {
        /** @var ReflectionClass<object> $class */
        $class = new ReflectionClass(FakePostConstructCounter::class);
        $newInstance = new NewInstance($class, new SetterMethods([]));
        $dependency = new Dependency($newInstance, new ReflectionMethod(FakePostConstructCounter::class, 'onPostConstruct'));
        $dependency->setScope(Scope::SINGLETON);
        $container = new Container();

        $first = $dependency->injectWithArgs($container, ['first']);
        $second = $dependency->injectWithArgs($container, ['second']);
        assert($first instanceof FakePostConstructCounter);
        assert($second instanceof FakePostConstructCounter);

        $this->assertSame($first, $second);
        // value comes from the FIRST call's arg, proving the second call was short-circuited
        $this->assertSame('first', $second->value);
        // postConstruct ran exactly once, not on every injectWithArgs() call
        $this->assertSame(1, $second->postConstructCount);
    }
}
