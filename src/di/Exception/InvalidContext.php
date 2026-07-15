<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use InvalidArgumentException;

/**
 * Thrown when a contextual provider context is not a string
 *
 * Message format: {actual type}
 *
 * No longer thrown: toProvider() now declares its $context parameter as
 * string, so PHP enforces this first. Kept for backward compatibility.
 */
final class InvalidContext extends InvalidArgumentException implements ExceptionInterface
{
}
