<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use InvalidArgumentException;

final class InvalidContext extends InvalidArgumentException implements ExceptionInterface
{
}
