<?php

declare(strict_types=1);

namespace Ray\Di;

use RuntimeException;

use Ray\Di\Di\PostConstruct;

/**
 * A singleton whose @PostConstruct throws on the first call and succeeds on the
 * second, used to verify the singleton cache is rolled back when PostConstruct
 * fails so the next resolution rebuilds instead of returning a half-initialized
 * instance.
 */
class FakePostConstructRetrySingleton
{
    public static int $constructCount = 0;
    public static int $postConstructCount = 0;
    public static bool $initialized = false;

    public function __construct()
    {
        self::$constructCount++;
    }

    #[PostConstruct]
    public function initialize(): void
    {
        self::$postConstructCount++;

        if (self::$postConstructCount === 1) {
            throw new RuntimeException('PostConstruct failed on first call');
        }

        self::$initialized = true;
    }

    public static function reset(): void
    {
        self::$constructCount = 0;
        self::$postConstructCount = 0;
        self::$initialized = false;
    }
}