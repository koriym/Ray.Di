<?php

declare(strict_types=1);

namespace Ray\Di;

/**
 * Counts how many times PHP revives an instance of this class,
 * so a test can tell whether a bound instance was reconstructed.
 */
final class FakeUnserializeSpy
{
    public static int $wakeups = 0;

    public function __wakeup(): void
    {
        self::$wakeups++;
    }
}
