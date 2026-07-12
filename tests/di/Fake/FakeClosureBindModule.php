<?php

declare(strict_types=1);

namespace Ray\Di;

/**
 * Binds a closure via toInstance() — an unserializable value.
 *
 * Used to prove ModuleString renders such a binding instead of throwing, the
 * way the former serialize()-based deep copy did.
 */
class FakeClosureBindModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind('')->annotatedWith('callback')->toInstance(static fn (): int => 1);
    }
}
