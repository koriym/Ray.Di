<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use Ray\Di\Di\Set;
use Ray\Di\Exception\SetNotFound;
use Ray\Di\InjectionPointInterface;
use Ray\Di\InjectorInterface;
use Ray\Di\ProviderInterface;

/**
 * @implements ProviderInterface<Map>
 */
final class MapProvider implements ProviderInterface
{
    /** @var MultiBindings */
    private $multiBindings;

    /** @var InjectionPointInterface */
    private $ip;

    /** @var InjectorInterface */
    private $injector;

    public function __construct(
        InjectionPointInterface $ip,
        MultiBindings $multiBindings,
        InjectorInterface $injector
    ) {
        $this->multiBindings = $multiBindings;
        $this->ip = $ip;
        $this->injector = $injector;
    }

    /**
     * @return Map<mixed>
     */
    public function get(): Map
    {
        $param = $this->ip->getParameter();
        $setAttribute = $param->getAttributes(Set::class);
        if (! isset($setAttribute[0])) {
            throw new SetNotFound((string) $param);
        }

        /** @var Set<object> $set */
        $set = $setAttribute[0]->newInstance();

        /** @var array<string, LazyTo<object>> $lazies */
        $lazies = $this->multiBindings[$set->interface];

        return new Map($lazies, $this->injector);
    }
}
