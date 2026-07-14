<?php

declare(strict_types=1);

namespace Ray\Di\Di;

use Attribute;

/**
 * @template T of object
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class Set
{
    /** @param ''|class-string<T> $interface */
    public function __construct(public string $interface, public string $name = '')
    {
    }
}
