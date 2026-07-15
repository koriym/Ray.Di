<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use LogicException;

/**
 * Thrown when a class, interface, or provider given to a binding does not exist
 *
 * Message format: {class or interface name}
 *
 * Raised while validating bind(), to(), and toProvider() arguments.
 */
final class NotFound extends LogicException implements ExceptionInterface
{
}
