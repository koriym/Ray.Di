<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use LogicException;

/** Bindings were rendered before a module snapshot was collected. */
final class BindingsNotCollected extends LogicException implements ExceptionInterface
{
}
