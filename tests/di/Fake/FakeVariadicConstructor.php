<?php

declare(strict_types=1);

namespace Ray\Di;

class FakeVariadicConstructor
{
    /** @var array<FakeEngineInterface> */
    public array $engines;

    public function __construct(FakeEngineInterface ...$engines)
    {
        $this->engines = $engines;
    }
}
