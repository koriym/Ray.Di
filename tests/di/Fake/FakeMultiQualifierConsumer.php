<?php

declare(strict_types=1);

namespace Ray\Di;

class FakeMultiQualifierConsumer
{
    #[FakeLeft]
    #[FakeRight]
    public function __construct(FakeEngineInterface $engine)
    {
    }
}
