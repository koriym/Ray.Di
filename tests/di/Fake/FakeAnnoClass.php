<?php

declare(strict_types=1);

namespace Ray\Di;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class FakeAnnoClass
{
    public static $order = [];
}
