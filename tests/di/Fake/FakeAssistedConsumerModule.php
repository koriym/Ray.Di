<?php

declare(strict_types=1);

namespace Ray\Di;

final class FakeAssistedConsumerModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(FakeAssistedConsumer::class);
        $this->bind(FakeAssistedInjectConsumer::class);
    }
}
