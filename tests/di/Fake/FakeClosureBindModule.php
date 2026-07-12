<?php

declare(strict_types=1);

namespace Ray\Di;

/**
 * Binds a closure via toInstance() — an unserializable value.
 *
 * ModuleString serializes the container, so composing this makes bindings.md
 * emission fail; construction must still succeed (best-effort).
 */
class FakeClosureBindModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind('')->annotatedWith('callback')->toInstance(static fn (): int => 1);
    }
}
