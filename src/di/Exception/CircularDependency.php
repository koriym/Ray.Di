<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use LogicException;

/**
 * Exception thrown when dependency resolution enters a cycle
 *
 * Message format: resolution path '{interface}-{name} -> {interface}-{name} -> ...'
 *
 * Deliberately not extending Unbound: default-value and optional-setter
 * fallbacks catch Unbound, and a dependency cycle must not be silently
 * swallowed by those recovery paths.
 */
final class CircularDependency extends LogicException implements ExceptionInterface
{
}
