<?php

declare(strict_types=1);

namespace Ray\Di;

final class ProviderSetModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(ProviderInterface::class)->toProvider(ProviderSetProvider::class);
    }
}
