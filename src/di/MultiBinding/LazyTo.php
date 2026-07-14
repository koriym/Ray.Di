<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use Ray\Di\InjectorInterface;

/** @template T of object */
final class LazyTo implements LazyInterface
{
    /** @param class-string<T> $class */
    public function __construct(private string $class)
    {
    }

    /** @return T */
    public function __invoke(InjectorInterface $injector)
    {
        return $injector->getInstance($this->class);
    }
}
