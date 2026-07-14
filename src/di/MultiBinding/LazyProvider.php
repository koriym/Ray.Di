<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use Ray\Di\InjectorInterface;
use Ray\Di\ProviderInterface;

/** @template T of ProviderInterface */
final class LazyProvider implements LazyInterface
{
    /** @param class-string<T> $class */
    public function __construct(private string $class)
    {
    }

    /** @return mixed */
    public function __invoke(InjectorInterface $injector)
    {
        $provider = $injector->getInstance($this->class);

        return $provider->get();
    }
}
