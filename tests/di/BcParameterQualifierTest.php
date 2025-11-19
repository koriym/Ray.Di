<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Di\Annotation\FakeInjectOne;
use ReflectionMethod;

class BcParameterQualifierTest extends TestCase
{
    public function testGetNamesFromMethodLevelAttribute(): void
    {
        $method = new ReflectionMethod(FakeBcParameterQualifierClass::class, 'setSingleParam');
        /** @psalm-suppress DeprecatedClass */
        $names = BcParameterQualifier::getNames($method);

        $this->assertSame(['param' => FakeInjectOne::class], $names);
    }

    public function testNoNamesForMultipleParameters(): void
    {
        $method = new ReflectionMethod(FakeBcParameterQualifierClass::class, 'setMultipleParams');
        /** @psalm-suppress DeprecatedClass */
        $names = BcParameterQualifier::getNames($method);

        $this->assertSame([], $names);
    }

    public function testNoNamesWhenParameterHasQualifier(): void
    {
        $method = new ReflectionMethod(FakeBcParameterQualifierClass::class, 'setSingleParamWithQualifier');
        /** @psalm-suppress DeprecatedClass */
        $names = BcParameterQualifier::getNames($method);

        $this->assertSame([], $names);
    }

    public function testNoNamesForNonQualifierAttribute(): void
    {
        $method = new ReflectionMethod(FakeBcParameterQualifierClass::class, 'setSingleParamWithInjectOnly');
        /** @psalm-suppress DeprecatedClass */
        $names = BcParameterQualifier::getNames($method);

        $this->assertSame([], $names);
    }

    public function testNoNamesForMethodWithoutInjectInterface(): void
    {
        $method = new ReflectionMethod(FakeBcParameterQualifierClass::class, 'setSingleParamNoInject');
        /** @psalm-suppress DeprecatedClass */
        $names = BcParameterQualifier::getNames($method);

        $this->assertSame([], $names);
    }

    public function testNoNamesForTargetMethodOnly(): void
    {
        // FakeGearStickInject has TARGET_METHOD only, not TARGET_PARAMETER
        // It's meant for Provider/InjectionPoint pattern, not parameter binding
        $method = new ReflectionMethod(FakeBcParameterQualifierClass::class, 'setSingleParamMethodOnly');
        /** @psalm-suppress DeprecatedClass */
        $names = BcParameterQualifier::getNames($method);

        $this->assertSame([], $names);
    }
}
