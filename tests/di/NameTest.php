<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use ReflectionParameter;

class NameTest extends TestCase
{
    public function testUnName(): void
    {
        $name = new Name(Name::ANY);
        $parameter = new ReflectionParameter([FakeCar::class, '__construct'], 'engine');
        $boundName = $name($parameter);
        $this->assertSame(Name::ANY, $boundName);
    }

    public function testSingleName(): void
    {
        $name = new Name('turbo');
        $parameter = new ReflectionParameter([FakeCar::class, '__construct'], 'engine');
        $boundName = $name($parameter);
        $this->assertSame('turbo', $boundName);
    }

    public function testSetName(): void
    {
        $name = new Name(FakeMirrorRight::class);
        $parameter = new ReflectionParameter([FakeHandleBar::class, 'setMirrors'], 'rightMirror');
        $boundName = $name($parameter);
        $expected = FakeMirrorRight::class;
        $this->assertSame($expected, $boundName);
    }
}
