<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use LogicException;

final class NotFound extends LogicException implements ExceptionInterface
{
}
