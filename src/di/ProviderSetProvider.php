<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\Set;
use Ray\Di\Exception\SetNotFound;

/**
 * @implements ProviderInterface<mixed>
 * @template T of object
 */
final class ProviderSetProvider implements ProviderInterface
{
    /** @var InjectionPointInterface */
    private $ip;

    /** @var InjectorInterface */
    private $injector;

    public function __construct(
        InjectionPointInterface $ip,
        InjectorInterface $injector
    ) {
        $this->ip = $ip;
        $this->injector = $injector;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        $param = $this->ip->getParameter();
        $setAttribute = $param->getAttributes(Set::class);
        if (! isset($setAttribute[0])) {
            throw new SetNotFound((string) $this->ip->getParameter());
        }

        $set = $setAttribute[0]->newInstance();

        return new ProviderProvider($this->injector, $set);
    }
}
