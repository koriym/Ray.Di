<?php

declare(strict_types=1);

namespace Ray\Di\Di;

use Attribute;

/**
 * Marks a parameter for assisted injection
 *
 * When applied to a method parameter, this indicates that the parameter
 * should be automatically injected by the DI container during method invocation.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class Assisted
{
}
