<?php

declare(strict_types=1);

namespace Ray\Di\Di;

use Attribute;

/**
 * Identifies qualifier annotations
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Qualifier
{
}
