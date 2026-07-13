<?php

declare(strict_types=1);

namespace Ray\Di;

class FakeBindingLogInnerModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(FakeRobotInterface::class)->to(FakeRobot::class);
    }
}
