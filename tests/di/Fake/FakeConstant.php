<?php

declare(strict_types=1);

namespace Ray\Di;

use Attribute;
use Ray\Di\Di\Qualifier;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
#[Qualifier]
final class FakeConstant
{
    public function __construct(
        public $value = null
    ) {
    }
}
