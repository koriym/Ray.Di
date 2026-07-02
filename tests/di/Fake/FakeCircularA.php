<?php

declare(strict_types=1);

namespace Ray\Di;

class FakeCircularA implements FakeCircularAInterface
{
    public function __construct(
        public FakeCircularBInterface $b
    ) {
    }
}
