<?php

declare(strict_types=1);

namespace Ray\Di;

class FakeCircularB implements FakeCircularBInterface
{
    public function __construct(
        public FakeCircularAInterface $a
    ) {
    }
}
