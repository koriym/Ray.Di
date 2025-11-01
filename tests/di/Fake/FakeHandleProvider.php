<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

class FakeHandleProvider implements ProviderInterface
{
    private $logo;

    #[Inject]
    public function __construct(#[Named('logo')] $logo = 'nardi')
    {
        $this->logo = $logo;
    }

    public function get()
    {
        $handle = new FakeHandle();
        $handle->logo = $this->logo;

        return $handle;
    }
}
