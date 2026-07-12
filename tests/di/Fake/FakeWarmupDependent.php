<?php

declare(strict_types=1);

namespace Ray\Di;

class FakeWarmupDependent
{
    public function __construct(
        public FakeWarmup $warmup
    ) {
    }
}
