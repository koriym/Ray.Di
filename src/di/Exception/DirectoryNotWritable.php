<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use RuntimeException;

/**
 * Message format: {directory path}
 *
 * The Injector compiles AOP proxy classes into the given temp directory
 * (or the system temp directory), so it must be writable.
 */
final class DirectoryNotWritable extends RuntimeException implements ExceptionInterface
{
}
