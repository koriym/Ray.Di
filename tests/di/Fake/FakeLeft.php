<?php

declare(strict_types=1);

namespace Ray\Di;

use Attribute;
use Ray\Di\Di\Qualifier;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER)]
#[Qualifier]
class FakeLeft
{
    public function __construct(
        public $value = null
    ) {
    }
}
