<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Di\Annotation\FakeInjectOne;
use ReflectionMethod;

class LegacyMethodQualifierInferenceTest extends TestCase
{
    public function testInferNamesFromMethodLevelAttribute(): void
    {
        $method = new ReflectionMethod(FakeLegacyMethodQualifierClass::class, 'setSingleParam');
        /** @psalm-suppress DeprecatedClass */
        $names = LegacyMethodQualifierInference::inferNames($method);

        $this->assertSame(['param' => FakeInjectOne::class], $names);
    }

    public function testNoInferenceForMultipleParameters(): void
    {
        $method = new ReflectionMethod(FakeLegacyMethodQualifierClass::class, 'setMultipleParams');
        /** @psalm-suppress DeprecatedClass */
        $names = LegacyMethodQualifierInference::inferNames($method);

        $this->assertSame([], $names);
    }

    public function testNoInferenceWhenParameterHasQualifier(): void
    {
        $method = new ReflectionMethod(FakeLegacyMethodQualifierClass::class, 'setSingleParamWithQualifier');
        /** @psalm-suppress DeprecatedClass */
        $names = LegacyMethodQualifierInference::inferNames($method);

        $this->assertSame([], $names);
    }

    public function testNoInferenceForNonQualifierAttribute(): void
    {
        $method = new ReflectionMethod(FakeLegacyMethodQualifierClass::class, 'setSingleParamWithInjectOnly');
        /** @psalm-suppress DeprecatedClass */
        $names = LegacyMethodQualifierInference::inferNames($method);

        $this->assertSame([], $names);
    }

    public function testNoInferenceForMethodWithoutInjectInterface(): void
    {
        $method = new ReflectionMethod(FakeLegacyMethodQualifierClass::class, 'setSingleParamNoInject');
        /** @psalm-suppress DeprecatedClass */
        $names = LegacyMethodQualifierInference::inferNames($method);

        $this->assertSame([], $names);
    }

    public function testNoInferenceForTargetMethodOnly(): void
    {
        // FakeGearStickInject has TARGET_METHOD only, not TARGET_PARAMETER
        // It's meant for Provider/InjectionPoint pattern, not parameter binding
        $method = new ReflectionMethod(FakeLegacyMethodQualifierClass::class, 'setSingleParamMethodOnly');
        /** @psalm-suppress DeprecatedClass */
        $names = LegacyMethodQualifierInference::inferNames($method);

        $this->assertSame([], $names);
    }
}
