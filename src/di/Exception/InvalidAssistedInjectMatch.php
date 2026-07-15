<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use LogicException;

/**
 * Thrown when AssistedInjectMatcher::matchesClass() is called
 *
 * This matcher is intended for method matching only, so it must not be
 * used as a class matcher.
 */
final class InvalidAssistedInjectMatch extends LogicException implements ExceptionInterface
{
}
