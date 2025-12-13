<?php

declare(strict_types=1);

namespace Ray\Di;

class FakeUnNamedModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(FakeUnNamedClass::class);
    }
}
