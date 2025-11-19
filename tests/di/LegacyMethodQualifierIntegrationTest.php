<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;

class LegacyMethodQualifierIntegrationTest extends TestCase
{
    public function testLegacyInferenceWorks(): void
    {
        $injector = new Injector(new FakeLegacyInferenceModule());
        /** @psalm-suppress DeprecatedClass */
        $instance = $injector->getInstance(FakeClassWithLegacyInference::class);

        // The setGearStick method uses #[FakeInjectOne] at method level only
        // Our legacy inference should apply it to the parameter
        $this->assertInstanceOf(FakeLeatherGearStick::class, $instance->gearStick);
    }
}
