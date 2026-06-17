<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\PostConstruct;

class FakePostConstructCounter
{
    public int $postConstructCount = 0;

    public function __construct(public string $value)
    {
    }

    #[PostConstruct]
    public function onPostConstruct(): void
    {
        $this->postConstructCount++;
    }
}
