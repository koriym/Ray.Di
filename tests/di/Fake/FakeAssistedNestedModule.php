<?php

declare(strict_types=1);

namespace Ray\Di;

class FakeAssistedNestedModule extends FakeAssistedDbModule
{
    protected function configure(): void
    {
        parent::configure();
        $this->bind(FakeAssistedParamsConsumer::class);
    }
}
