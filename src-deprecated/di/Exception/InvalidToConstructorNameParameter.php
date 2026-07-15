<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use InvalidArgumentException;

/**
 * @deprecated No longer thrown: toConstructor() now declares the parameter as string|array, so PHP enforces this first.
 * @see https://github.com/ray-di/Ray.Di#constructor-bindings
 */
final class InvalidToConstructorNameParameter extends InvalidArgumentException implements ExceptionInterface
{
}
