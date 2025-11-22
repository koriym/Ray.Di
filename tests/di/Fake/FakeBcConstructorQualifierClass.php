<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Annotation\FakeQualifierOnly;

class FakeBcConstructorQualifierClass
{
    /**
     * Constructor with method-level Qualifier-only attribute (no InjectInterface)
     * This should trigger BC parameter qualifier for constructors
     */
    #[FakeQualifierOnly]
    public function __construct(public $param)
    {
    }
}
