<?php

declare(strict_types=1);

namespace Ray\Di;

class FakeHandleBarQualifier
{
    public function __construct(
        #[FakeRight('rightMirror')]
        public FakeMirrorInterface $rightMirror,
        #[FakeLeft('leftMirror')]
        public FakeMirrorInterface $leftMirror
    ) {
    }
}
