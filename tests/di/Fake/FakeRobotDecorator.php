<?php

declare(strict_types=1);

namespace Ray\Di;

/**
 * Wraps another FakeRobotInterface, exposing it to prove what a decorator received.
 */
class FakeRobotDecorator implements FakeRobotInterface
{
    public function __construct(
        public readonly FakeRobotInterface $inner
    ) {
    }
}
