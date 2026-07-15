<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use InvalidArgumentException;

/**
 * Message format: {invalid name value}
 *
 * The $name parameter maps constructor variable names to binding names as
 * a 'varName=bindName,...' string or a [varName => bindName] array.
 * No longer thrown: toConstructor() now declares the parameter as
 * string|array, so PHP enforces this first. Kept for backward compatibility.
 *
 * @see https://github.com/ray-di/Ray.Di#constructor-bindings
 */
final class InvalidToConstructorNameParameter extends InvalidArgumentException implements ExceptionInterface
{
}
