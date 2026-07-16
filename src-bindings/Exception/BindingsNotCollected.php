<?php

declare(strict_types=1);

namespace Ray\Bindings\Exception;

use LogicException;

/** Thrown when bindings are rendered before a module snapshot is collected. */
final class BindingsNotCollected extends LogicException
{
}
