<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use LogicException;

/**
 * Exception thrown when a multibinding Map is mutated through ArrayAccess
 *
 * Map is a read-only view over injected bindings, so writing or removing
 * an offset is not supported.
 */
final class ReadOnlyMapAccess extends LogicException implements ExceptionInterface
{
}
