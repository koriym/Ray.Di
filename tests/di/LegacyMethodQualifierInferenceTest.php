<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Di\Annotation\FakeInjectOne;
use ReflectionMethod;

class LegacyMethodQualifierInferenceTest extends TestCase
{
    public function testInferQualifierFromMethodLevelAttribute(): void
    {
        $method = new ReflectionMethod(FakeLegacyMethodQualifierClass::class, 'setSingleParam');
        /** @psalm-suppress DeprecatedClass */
        $qualifier = LegacyMethodQualifierInference::inferQualifier($method);

        $this->assertSame(FakeInjectOne::class, $qualifier);
    }

    public function testNoInferenceForMultipleParameters(): void
    {
        $method = new ReflectionMethod(FakeLegacyMethodQualifierClass::class, 'setMultipleParams');
        /** @psalm-suppress DeprecatedClass */
        $qualifier = LegacyMethodQualifierInference::inferQualifier($method);

        $this->assertSame('', $qualifier);
    }

    public function testNoInferenceWhenParameterHasQualifier(): void
    {
        $method = new ReflectionMethod(FakeLegacyMethodQualifierClass::class, 'setSingleParamWithQualifier');
        /** @psalm-suppress DeprecatedClass */
        $qualifier = LegacyMethodQualifierInference::inferQualifier($method);

        $this->assertSame('', $qualifier);
    }

    public function testNoInferenceForNonQualifierAttribute(): void
    {
        $method = new ReflectionMethod(FakeLegacyMethodQualifierClass::class, 'setSingleParamWithInjectOnly');
        /** @psalm-suppress DeprecatedClass */
        $qualifier = LegacyMethodQualifierInference::inferQualifier($method);

        $this->assertSame('', $qualifier);
    }

    public function testNoInferenceForMethodWithoutInjectInterface(): void
    {
        $method = new ReflectionMethod(FakeLegacyMethodQualifierClass::class, 'setSingleParamNoInject');
        /** @psalm-suppress DeprecatedClass */
        $qualifier = LegacyMethodQualifierInference::inferQualifier($method);

        $this->assertSame('', $qualifier);
    }

    public function testNoInferenceForTargetMethodOnly(): void
    {
        // FakeGearStickInject has TARGET_METHOD only, not TARGET_PARAMETER
        // It's meant for Provider/InjectionPoint pattern, not parameter binding
        $method = new ReflectionMethod(FakeLegacyMethodQualifierClass::class, 'setSingleParamMethodOnly');
        /** @psalm-suppress DeprecatedClass */
        $qualifier = LegacyMethodQualifierInference::inferQualifier($method);

        $this->assertSame('', $qualifier);
    }
}
