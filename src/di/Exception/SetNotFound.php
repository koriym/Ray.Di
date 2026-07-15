<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use LogicException;

/**
 * Thrown when a Map or Provider injection point has no #[Set] attribute
 *
 * Message format: {injection point parameter}
 *
 * Multibinding injection requires #[Set] to identify which binding set to inject.
 */
final class SetNotFound extends LogicException implements ExceptionInterface
{
}
