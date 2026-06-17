<?php

declare(strict_types=1);

namespace Ray\Di;

class FakeNullProvider implements ProviderInterface
{
    /** @var int */
    public static $count = 0;

    /**
     * Legitimately provides null (e.g. an optional dependency).
     */
    public function get()
    {
        self::$count++;

        return null;
    }
}
