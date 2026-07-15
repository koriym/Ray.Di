<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use InvalidArgumentException;

/**
 * @deprecated No longer thrown: toProvider() now declares its $context parameter as string, so PHP enforces this first.
 */
final class InvalidContext extends InvalidArgumentException implements ExceptionInterface
{
}
