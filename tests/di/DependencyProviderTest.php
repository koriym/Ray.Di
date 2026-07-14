<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DependencyProviderTest extends TestCase
{
    /**
     * A singleton provider may legitimately return null. The instance must be
     * cached on the FIRST call and never produced again, so the provider runs
     * exactly once. Tracking instantiation through `$instance !== null` instead
     * of an explicit flag re-runs the provider on every call when it returns
     * null, breaking the singleton contract.
     */
    public function testSingletonProviderReturningNullIsInstantiatedOnce(): void
    {
        FakeNullProvider::$count = 0;
        /** @var ReflectionClass<object> $class */
        $class = new ReflectionClass(FakeNullProvider::class);
        $dependency = new Dependency(new NewInstance($class, new SetterMethods([])));
        $dependencyProvider = new DependencyProvider($dependency, 'context');
        $dependencyProvider->setScope(Scope::SINGLETON);
        $container = new Container();

        $first = $dependencyProvider->inject($container);
        $second = $dependencyProvider->inject($container);

        $this->assertNull($first);
        $this->assertNull($second);
        $this->assertSame(1, FakeNullProvider::$count);
    }
}
