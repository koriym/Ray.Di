<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use InvalidArgumentException;

/**
 * Thrown when a toProvider() class does not implement ProviderInterface
 *
 * Message format: {provider class name}
 */
final class InvalidProvider extends InvalidArgumentException implements ExceptionInterface
{
}
