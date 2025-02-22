<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use RuntimeException;

final class DirectoryNotWritable extends RuntimeException implements ExceptionInterface
{
}
