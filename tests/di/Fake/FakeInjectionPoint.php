<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\Named;
use ReflectionParameter;

class FakeInjectionPoint implements ProviderInterface
{
    public $ip;

    public function __construct(#[Named('aa')] ReflectionParameter $ip)
    {
        $this->ip = $ip;
    }

    public function get()
    {
        if ($this->ip->getName()) {
            return $this->ip;
        }
    }
}
