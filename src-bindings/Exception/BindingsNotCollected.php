<?php

declare(strict_types=1);

namespace Ray\Bindings\Exception;

use LogicException;

/** Bindings were rendered before a module snapshot was collected. */
final class BindingsNotCollected extends LogicException
{
}
