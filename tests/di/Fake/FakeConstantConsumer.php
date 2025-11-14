<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\Inject;

class FakeConstantConsumer
{
    public $constantByConstruct;
    public $constantBySetter;
    public $defaultByConstruct;
    public $defaultBySetter;
    public $setterConstantWithoutVarName;

    public function __construct(#[FakeConstant('constant')] $constant, $default = 'default_construct')
    {
        $this->constantByConstruct = $constant;
        $this->defaultByConstruct = $default;
    }

    #[Inject]
    public function setFakeConstant(#[FakeConstant('constant')] $constant, $default = 'default_setter'): void
    {
        $this->constantBySetter = $constant;
        $this->defaultBySetter = $default;
    }

    #[Inject]
    public function setFakeConstantWithoutVarName(#[FakeConstant] $constant): void
    {
        $this->setterConstantWithoutVarName = $constant;
    }
}
