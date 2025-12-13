<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Di\Exception\NoHint;
use Ray\Di\Exception\Unbound;

class NoHintTest extends TestCase
{
    public function testNoHintIsUnbound(): void
    {
        $e = new NoHint();
        $this->assertInstanceOf(Unbound::class, $e);
    }

    public function testNoHintThrownForNoTypeNoName(): void
    {
        $this->expectException(NoHint::class);

        $injector = new Injector(new FakeUnNamedModule());
        $injector->getInstance(FakeUnNamedClass::class);
    }
}
