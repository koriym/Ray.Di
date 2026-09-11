<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Di\MultiBinding\Map;

use function array_keys;
use function array_map;
use function assert;
use function serialize;
use function unserialize;

/**
 * A revived container reconstructs a binding only when that binding is
 * requested; every other observable behavior matches the original container.
 */
class ContainerUnserializeTest extends TestCase
{
    protected function setUp(): void
    {
        FakeUnserializeSpy::$wakeups = 0;
    }

    public function testBindingIsNotRevivedUntilRequested(): void
    {
        $injector = new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeEngineInterface::class)->to(FakeEngine::class);
                $this->bind(FakeUnserializeSpy::class)->toInstance(new FakeUnserializeSpy());
            }
        });
        $revived = unserialize(serialize($injector));
        assert($revived instanceof Injector);
        $this->assertSame(0, FakeUnserializeSpy::$wakeups, 'unserialize() of the injector revived a binding');

        $revived->getInstance(FakeEngineInterface::class);
        $this->assertSame(0, FakeUnserializeSpy::$wakeups, 'resolving another binding revived the spy binding');

        $spy = $revived->getInstance(FakeUnserializeSpy::class);
        $this->assertInstanceOf(FakeUnserializeSpy::class, $spy);
        $this->assertSame(1, FakeUnserializeSpy::$wakeups);

        $this->assertSame($spy, $revived->getInstance(FakeUnserializeSpy::class));
        $this->assertSame(1, FakeUnserializeSpy::$wakeups, 'a requested binding must be revived once');
    }

    public function testInjectorBindingIsTheRevivedInjector(): void
    {
        $revived = unserialize(serialize(new Injector(new FakeCarModule())));
        assert($revived instanceof Injector);

        $this->assertSame($revived, $revived->getInstance(InjectorInterface::class));
    }

    public function testSingletonIdentitySurvivesUnserialize(): void
    {
        $revived = unserialize(serialize(new Injector(new FakeCarModule())));
        assert($revived instanceof Injector);

        $mirror = $revived->getInstance(FakeMirrorInterface::class, 'right');
        $this->assertInstanceOf(FakeMirrorRight::class, $mirror);
        $this->assertSame($mirror, $revived->getInstance(FakeMirrorInterface::class, 'right'));
        $car = $revived->getInstance(FakeCarInterface::class);
        assert($car instanceof FakeCar);
        $this->assertSame($mirror, $car->rightMirror);
        $this->assertSame($mirror, $car->spareMirror);
    }

    public function testMultiBindingResolvesAfterUnserialize(): void
    {
        $revived = unserialize(serialize(new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeMultiBindingConsumer::class);
                $this->bind(FakeEngine::class);
                $this->bind(FakeEngine2::class);
                $this->bind(FakeRobot::class);
                $engineBinder = MultiBinder::newInstance($this, FakeEngineInterface::class);
                $engineBinder->addBinding('one')->to(FakeEngine::class);
                $engineBinder->addBinding('two')->to(FakeEngine2::class);
                $robotBinder = MultiBinder::newInstance($this, FakeRobotInterface::class);
                $robotBinder->addBinding('to')->to(FakeRobot::class);
            }
        })));
        assert($revived instanceof Injector);

        $consumer = $revived->getInstance(FakeMultiBindingConsumer::class);
        assert($consumer instanceof FakeMultiBindingConsumer);
        $this->assertInstanceOf(Map::class, $consumer->engines);
        $this->assertInstanceOf(FakeEngine::class, $consumer->engines['one']);
        $this->assertInstanceOf(FakeEngine2::class, $consumer->engines['two']);
        $this->assertInstanceOf(FakeRobot::class, $consumer->robots['to']);
    }

    public function testGetContainerReturnsEveryBindingAfterUnserialize(): void
    {
        $container = new Container();
        (new Bind($container, FakeEngineInterface::class))->to(FakeEngine::class);
        (new Bind($container, FakeCarInterface::class))->to(FakeCar::class)->in(Scope::SINGLETON);
        (new Bind($container, FakeUnserializeSpy::class))->toInstance(new FakeUnserializeSpy());
        $expected = array_map(
            static fn (DependencyInterface $dependency): string => (string) $dependency,
            $container->getContainer()
        );

        $revived = unserialize(serialize($container));
        assert($revived instanceof Container);
        $bindings = $revived->getContainer();

        $this->assertSame(array_keys($expected), array_keys($bindings));
        $this->assertContainsOnlyInstancesOf(DependencyInterface::class, $bindings);
        $this->assertSame($expected, array_map(
            static fn (DependencyInterface $dependency): string => (string) $dependency,
            $bindings
        ));
    }
}
