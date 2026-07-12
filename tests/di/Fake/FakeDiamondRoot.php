<?php

declare(strict_types=1);

namespace Ray\Di;

class FakeDiamondRoot
{
    public function __construct(
        public FakeDiamondSharedInterface $left,
        public FakeDiamondSharedInterface $right
    ) {
    }
}
