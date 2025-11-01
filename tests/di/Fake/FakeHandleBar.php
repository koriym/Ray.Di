<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\Inject;

class FakeHandleBar
{
    public $rightMirror;
    public $leftMirror;

    #[Inject]
    public function setMirrors(#[FakeRight] FakeMirrorInterface $rightMirror): void
    {
        $this->rightMirror = $rightMirror;
    }

    #[Inject]
    public function setLeftMirror(#[FakeLeft] FakeMirrorInterface $leftMirror): void
    {
        $this->leftMirror = $leftMirror;
    }
}
