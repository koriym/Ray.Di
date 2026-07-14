<?php

declare(strict_types=1);

namespace Ray\Di;

/** Binds an unserializable closure via toInstance(). */
class FakeClosureBindModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind('')->annotatedWith('callback')->toInstance(static fn (): int => 1);
    }
}
