<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use InvalidArgumentException;

/**
 * Thrown when a to() class does not implement the bound interface
 *
 * Binding an interface to a class requires the class to be a subtype
 * of that interface.
 */
final class InvalidType extends InvalidArgumentException implements ExceptionInterface
{
}
