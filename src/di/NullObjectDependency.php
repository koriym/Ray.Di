<?php

declare(strict_types=1);

namespace Ray\Di;

use Koriym\NullObject\NullObject;
use ReflectionClass;

use function assert;
use function is_dir;

/** @codeCoverageIgnore */
final class NullObjectDependency implements DependencyInterface
{
    /** @param class-string $interface */
    public function __construct(private string $interface)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function inject(Container $container): null
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function register(array &$container, Bind $bind): void
    {
        $container[(string) $bind] = $bind->getBound();
    }

    /**
     * {@inheritDoc}
     */
    public function setScope($scope): void
    {
    }

    public function toNull(string $scriptDir): Dependency
    {
        assert(is_dir($scriptDir));
        $nullObject = new NullObject();
        $class = $nullObject->save($this->interface, $scriptDir);

        return new Dependency(new NewInstance(new ReflectionClass($class), new SetterMethods([])));
    }
}
