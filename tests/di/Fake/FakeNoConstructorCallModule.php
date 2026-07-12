<?php

declare(strict_types=1);

namespace Ray\Di;

/**
 * Module whose constructor does not call parent::__construct()
 *
 * configure() must still run, lazily, on the first getContainer() call.
 */
class FakeNoConstructorCallModule extends AbstractModule
{
    public function __construct()
    {
    }

    protected function configure(): void
    {
        $this->bind(FakeRobotInterface::class)->to(FakeRobot::class);
    }
}
