<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Annotation\FakeInjectOne;

class FakeLegacyInferenceModule extends AbstractModule
{
    protected function configure(): void
    {
        // Bind with FakeInjectOne qualifier
        $this->bind(FakeGearStickInterface::class)
            ->annotatedWith(FakeInjectOne::class)
            ->to(FakeLeatherGearStick::class);
    }
}
