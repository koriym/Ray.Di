<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\Set;

/**
 * @implements ProviderInterface<mixed>
 * @template T of object
 */
final class ProviderProvider implements ProviderInterface
{
    /** @param Set<T> $set */
    public function __construct(private InjectorInterface $injector, private Set $set)
    {
    }

    /** @return mixed */
    public function get()
    {
        return $this->injector->getInstance($this->set->interface, $this->set->name);
    }
}
