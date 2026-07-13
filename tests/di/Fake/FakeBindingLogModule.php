<?php

declare(strict_types=1);

namespace Ray\Di;

/**
 * Exercises every composition write the BindingLog must record
 *
 * install() adopts FakeBindingLogInstalledModule's FakeRobotInterface binding,
 * the two FakeEngineInterface binds are a deliberate same-module replace, and
 * the constructor-chained module (merged after configure()) collides with the
 * installed FakeRobotInterface binding, producing a keep/discard.
 */
class FakeBindingLogModule extends AbstractModule
{
    protected function configure()
    {
        $this->install(new FakeBindingLogInstalledModule());
        $this->bind(FakeEngineInterface::class)->to(FakeEngine::class);
        $this->bind(FakeEngineInterface::class)->to(FakeEngine2::class);
    }
}
