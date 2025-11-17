<?php

declare(strict_types=1);

namespace Ray\Di\Di;

use Attribute;

/**
 * Annotates named things
 *
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
final class Named
{
    public function __construct(public string $value)
    {
    }
}
