<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use Ray\Di\InjectorInterface;

/**
 * @template T of mixed
 * @psalm-immutable
 */
final class LazyInstance implements LazyInterface
{
    /**
     * @param T $instance
     */
    public function __construct(private $instance)
    {
    }

    /**
     * @return T
     */
    public function __invoke(InjectorInterface $injector)
    {
        unset($injector);

        return $this->instance;
    }
}
