<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use ArrayAccess;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Exception\ReadOnlyMapAccess;
use Ray\Di\Exception\SetNotFound;
use Ray\Di\FakeEngine;
use Ray\Di\FakeEngine2;
use Ray\Di\FakeEngine3;
use Ray\Di\FakeEngineInterface;
use Ray\Di\FakeMultiBindingAnnotation;
use Ray\Di\FakeMultiBindingConsumer;
use Ray\Di\FakeRobot;
use Ray\Di\FakeRobotInterface;
use Ray\Di\FakeRobotProvider;
use Ray\Di\FakeSetNotFoundWithMap;
use Ray\Di\FakeSetNotFoundWithProvider;
use Ray\Di\Injector;
use Ray\Di\MultiBinder;
use Ray\Di\NullModule;

use function array_keys;
use function count;
use function iterator_to_array;

class MultiBindingModuleTest extends TestCase
{
    /** @var AbstractModule */
    private $module;

    protected function setUp(): void
    {
        $this->module = new class extends AbstractModule {
            protected function configure(): void
            {
                $engineBinder = MultiBinder::newInstance($this, FakeEngineInterface::class);
                $engineBinder->addBinding('one')->to(FakeEngine::class);
                $engineBinder->addBinding('two')->to(FakeEngine2::class);
                $engineBinder->addBinding()->to(FakeEngine3::class);
                $robotBinder = MultiBinder::newInstance($this, FakeRobotInterface::class);
                $robotBinder->addBinding('to')->to(FakeRobot::class);
                $robotBinder->addBinding('provider')->toProvider(FakeRobotProvider::class);
                $robotBinder->addBinding('instance')->toInstance(new FakeRobot());
            }
        };
    }

    /** @return Map<FakeEngineInterface> */
    public function testInjectMap(): Map
    {
        $injector = new Injector($this->module);
        $consumer = $injector->getInstance(FakeMultiBindingConsumer::class);
        $this->assertInstanceOf(Map::class, $consumer->engines);

        return $consumer->engines;
    }

    /** @param Map<object> $map */
    #[Depends('testInjectMap')]
    public function testMapInstance(Map $map): void
    {
        $this->assertInstanceOf(FakeEngine::class, $map['one']);
        $this->assertInstanceOf(FakeEngine2::class, $map['two']);
    }

    /** @param Map<object> $map */
    #[Depends('testInjectMap')]
    public function testMapIteration(Map $map): void
    {
        $this->assertContainsOnlyInstancesOf(FakeEngineInterface::class, $map);

        $this->assertSame(3, count($map));

        $items = iterator_to_array($map);
        // iteration order is declaration order; an unnamed addBinding() appends numerically
        $this->assertSame(['one', 'two', 0], array_keys($items));
        $this->assertInstanceOf(FakeEngine::class, $items['one']);
        $this->assertInstanceOf(FakeEngine2::class, $items['two']);
        $this->assertInstanceOf(FakeEngine3::class, $items[0]);
    }

    /** @param Map<object> $map */
    #[Depends('testInjectMap')]
    public function testIsSet(Map $map): void
    {
        $this->assertTrue(isset($map['one']));
        $this->assertTrue(isset($map['two']));
    }

    /** @param Map<object> $map */
    #[Depends('testInjectMap')]
    public function testOffsetSet(Map $map): void
    {
        try {
            $map['one'] = 1;
            self::fail('A read-only Map must not accept offsetSet().');
        } catch (ReadOnlyMapAccess $e) {
            self::assertSame('one', $e->getMessage());
        }
    }

    /** @param Map<object> $map */
    #[Depends('testInjectMap')]
    public function testOffsetUnset(Map $map): void
    {
        try {
            unset($map['one']);
            self::fail('A read-only Map must not accept offsetUnset().');
        } catch (ReadOnlyMapAccess $e) {
            self::assertSame('one', $e->getMessage());
        }
    }

    /**
     * `$map[] = $value` invokes offsetSet() with a null offset.
     *
     * @param Map<object> $map
     */
    #[Depends('testInjectMap')]
    public function testOffsetSetWithNullOffset(Map $map): void
    {
        $this->expectException(ReadOnlyMapAccess::class);
        $map[] = new FakeEngine();
    }

    /** @param Map<object> $map */
    #[Depends('testInjectMap')]
    public function testOffsetUnsetWithNullOffset(Map $map): void
    {
        $this->expectException(ReadOnlyMapAccess::class);
        $map->offsetUnset(null);
    }

    public function testAnotherBinder(): void
    {
        $injector = new Injector($this->module);
        $consumer = $injector->getInstance(FakeMultiBindingConsumer::class);
        $this->assertInstanceOf(Map::class, $consumer->robots);
        $this->assertContainsOnlyInstancesOf(FakeRobot::class, $consumer->robots);
        $this->assertSame(3, count($consumer->robots));
    }

    public function testMultipileModule(): void
    {
        $module = new NullModule();
        $binder = MultiBinder::newInstance($module, FakeEngineInterface::class);
        $binder->addBinding('one')->to(FakeEngine::class);
        $binder->addBinding('two')->to(FakeEngine2::class);
        $module->install(new class extends AbstractModule {
            protected function configure()
            {
                $binder = MultiBinder::newInstance($this, FakeEngineInterface::class);
                $binder->addBinding('three')->to(FakeEngine::class);
                $binder->addBinding('four')->to(FakeEngine::class);
            }
        });
        /** @var ArrayAccess<string, object> $multiBindings */
        $multiBindings = $module->getContainer()->getInstance(MultiBindings::class);
        $this->assertArrayHasKey('one', (array) $multiBindings[FakeEngineInterface::class]);
        $this->assertArrayHasKey('two', (array) $multiBindings[FakeEngineInterface::class]);
        $this->assertArrayHasKey('three', (array) $multiBindings[FakeEngineInterface::class]);
        $this->assertArrayHasKey('four', (array) $multiBindings[FakeEngineInterface::class]);
    }

    /**
     * MultiBindings resolves to the container's own store — the object
     * Container::merge() accumulates entries into — so what was merged is what
     * gets injected.
     *
     * Holds even with no MultiBinder in play: MultiBindingModule's binding is
     * what makes the index exist at all, and without it the request falls
     * through to just-in-time binding and yields an unrelated empty store.
     */
    public function testMultiBindingsResolvesToTheContainersOwnStore(): void
    {
        $module = new NullModule();
        $injector = new Injector($module);

        $this->assertSame($module->getContainer()->multiBindings, $injector->getInstance(MultiBindings::class));
    }

    public function testAnnotation(): void
    {
        $injector = new Injector($this->module);
        $fake = $injector->getInstance(FakeMultiBindingAnnotation::class);
        $this->assertContainsOnlyInstancesOf(FakeEngineInterface::class, $fake->engines);
        $this->assertSame(3, count($fake->engines));
        $this->assertContainsOnlyInstancesOf(FakeRobotInterface::class, $fake->robots);
        $this->assertSame(3, count($fake->robots));
    }

    public function testSetNotFoundInMap(): void
    {
        $this->expectException(SetNotFound::class);
        $injector = new Injector($this->module);
        $injector->getInstance(FakeSetNotFoundWithMap::class);
    }

    public function testSetNotFoundInProvider(): void
    {
        $this->expectException(SetNotFound::class);
        $injector = new Injector();
        $injector->getInstance(FakeSetNotFoundWithProvider::class);
    }
}
