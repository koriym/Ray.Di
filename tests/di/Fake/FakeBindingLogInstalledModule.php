<?php

declare(strict_types=1);

namespace Ray\Di;

class FakeBindingLogInstalledModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(FakeRobotInterface::class)->to(FakeRobot2::class);
    }
}
