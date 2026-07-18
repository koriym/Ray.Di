<?php

declare(strict_types=1);

namespace Ray\Di;

final class FakePrivateConstructor
{
    /** @param array<class-string> $classes */
    private function __construct(array $classes)
    {
    }
}
