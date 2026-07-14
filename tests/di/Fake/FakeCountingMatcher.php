<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\AbstractMatcher;
use ReflectionClass;
use ReflectionMethod;

final class FakeCountingMatcher extends AbstractMatcher
{
    public int $matches = 0;

    /** @param array<array-key, mixed> $arguments */
    public function matchesClass(ReflectionClass $class, array $arguments): bool
    {
        unset($class, $arguments);
        $this->matches++;

        return true;
    }

    /** @param array<array-key, mixed> $arguments */
    public function matchesMethod(ReflectionMethod $method, array $arguments): bool
    {
        unset($method, $arguments);

        return true;
    }
}
