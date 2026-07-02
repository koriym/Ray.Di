<?php

declare(strict_types=1);

namespace Ray\Di;

class FakeSelfCircular implements FakeSelfCircularInterface
{
    public function __construct(
        public FakeSelfCircularInterface $self
    ) {
    }
}
