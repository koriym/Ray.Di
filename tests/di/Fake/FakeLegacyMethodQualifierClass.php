<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Annotation\FakeInjectOne;
use Ray\Di\Di\Inject;

class FakeLegacyMethodQualifierClass
{
    public $singleParam;
    public $multipleParams1;
    public $multipleParams2;
    public $singleParamWithQualifier;
    public $singleParamWithInjectOnly;
    public $singleParamNoInject;

    /**
     * Single parameter with method-level InjectInterface+Qualifier
     * This should trigger legacy inference (FakeInjectOne supports TARGET_PARAMETER)
     */
    #[FakeInjectOne]
    public function setSingleParam(FakeGearStickInterface $param): void
    {
        $this->singleParam = $param;
    }

    /**
     * Multiple parameters - should NOT infer
     */
    #[FakeGearStickInject('test')]
    public function setMultipleParams(FakeGearStickInterface $param1, FakeTyreInterface $param2): void
    {
        $this->multipleParams1 = $param1;
        $this->multipleParams2 = $param2;
    }

    /**
     * Single parameter but already has parameter-level qualifier - should NOT infer
     */
    #[FakeGearStickInject('test')]
    public function setSingleParamWithQualifier(#[FakeInjectOne] FakeGearStickInterface $param): void
    {
        $this->singleParamWithQualifier = $param;
    }

    /**
     * Method attribute only implements InjectInterface, not Qualifier - should NOT infer
     */
    #[Inject]
    public function setSingleParamWithInjectOnly(FakeGearStickInterface $param): void
    {
        $this->singleParamWithInjectOnly = $param;
    }

    /**
     * No InjectInterface at all - should NOT infer
     */
    public function setSingleParamNoInject(FakeGearStickInterface $param): void
    {
        $this->singleParamNoInject = $param;
    }

    /**
     * Method-level attribute with TARGET_METHOD only (Provider pattern)
     * Should NOT infer because it doesn't support TARGET_PARAMETER
     */
    #[FakeGearStickInject('test')]
    public function setSingleParamMethodOnly(FakeGearStickInterface $param): void
    {
        // This is for Provider/InjectionPoint pattern, not parameter binding
    }
}
