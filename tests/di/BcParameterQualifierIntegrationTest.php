<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;

class BcParameterQualifierIntegrationTest extends TestCase
{
    public function testBcParameterQualifierWorks(): void
    {
        $injector = new Injector(new FakeBcParameterQualifierModule());
        /** @psalm-suppress DeprecatedClass */
        $instance = $injector->getInstance(FakeClassWithBcParameterQualifier::class);

        // The setGearStick method uses #[FakeInjectOne] at method level only
        // BcParameterQualifier should apply it to the parameter
        $this->assertInstanceOf(FakeLeatherGearStick::class, $instance->gearStick);
    }
}
