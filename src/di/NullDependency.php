<?php

declare(strict_types=1);

namespace Ray\Di;

/** @codeCoverageIgnore */
final class NullDependency implements DependencyInterface
{
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
    public function inject(Container $container): void
    {
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
}
