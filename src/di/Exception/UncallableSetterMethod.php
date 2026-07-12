<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use LogicException;

/**
 * Exception thrown when a resolved setter method cannot be called
 *
 * This is a defensive guard: the setter method is looked up on the
 * instance right before invocation, and reaching this branch would mean
 * the reflected method is no longer callable.
 */
final class UncallableSetterMethod extends LogicException implements ExceptionInterface
{
}
