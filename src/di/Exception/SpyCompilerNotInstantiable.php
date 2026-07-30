<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use LogicException;

/**
 * Thrown when SpyCompiler::newInstance() is called
 *
 * SpyCompiler only logs binding information and never produces real
 * instances, so this method is never expected to be invoked.
 */
final class SpyCompilerNotInstantiable extends LogicException implements ExceptionInterface
{
}
