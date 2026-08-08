<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use Ray\Di\Di\Set;
use Ray\Di\Exception\SetNotBound;
use Ray\Di\Exception\SetNotFound;
use Ray\Di\InjectionPointInterface;
use Ray\Di\InjectorInterface;
use Ray\Di\ProviderInterface;

use function sprintf;

/** @implements ProviderInterface<Map> */
final class MapProvider implements ProviderInterface
{
    public function __construct(private readonly InjectionPointInterface $ip, private MultiBindings $multiBindings, private readonly InjectorInterface $injector)
    {
    }

    /** @return Map<mixed> */
    public function get(): Map
    {
        $param = $this->ip->getParameter();
        $setAttribute = $param->getAttributes(Set::class);
        if (! isset($setAttribute[0])) {
            throw new SetNotFound((string) $param);
        }

        /** @var Set<object> $set */
        $set = $setAttribute[0]->newInstance();

        if (! $this->multiBindings->offsetExists($set->interface)) {
            throw new SetNotBound(sprintf(
                "'%s' in %s:%d ($%s)",
                $set->interface,
                $param->getDeclaringFunction()->getFileName(),
                $param->getDeclaringFunction()->getStartLine(),
                $param->getName()
            ));
        }

        /** @var array<string, LazyTo<object>> $lazies */
        $lazies = $this->multiBindings[$set->interface];

        return new Map($lazies, $this->injector);
    }
}
