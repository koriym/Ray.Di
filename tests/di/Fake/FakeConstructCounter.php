<?php

declare(strict_types=1);

namespace Ray\Di;

class FakeConstructCounter
{
    public static int $constructCount = 0;

    public function __construct()
    {
        self::$constructCount++;
    }
}
