<?php

declare(strict_types=1);

namespace Ray\Di;

/**
 * @codeCoverageIgnore
 */
final class NullDependency implements DependencyInterface
{
    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function inject(Container $container): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function register(array &$container, Bind $bind): void
    {
        $container[(string) $bind] = $bind->getBound();
    }

    /**
     * {@inheritdoc}
     */
    public function setScope($scope): void
    {
    }
}
