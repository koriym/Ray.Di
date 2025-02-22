<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use InvalidArgumentException;

final class InvalidProvider extends InvalidArgumentException implements ExceptionInterface
{
}
