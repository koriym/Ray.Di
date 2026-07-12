<?php

declare(strict_types=1);

namespace Ray\Di;

/**
 * A second, distinct implementation of FakeRobotInterface.
 *
 * Used to prove that a binding is (or is not) the one originally registered,
 * by asserting on which concrete class an interface resolves to.
 */
class FakeRobot2 implements FakeRobotInterface
{
}
