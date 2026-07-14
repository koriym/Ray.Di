<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('keyPairStringProvider')]
    public function testKeyValuePairName(string $keyPairValueString): void
    {
        $name = new Name($keyPairValueString);
        $parameter = new ReflectionParameter([FakeCar::class, '__construct'], 'engine');
        $boundName = $name($parameter);
        $this->assertSame('engine_name', $boundName);
    }

    /**
     * @return string[][]
     * @psalm-return array{0: array{0: string}, 1: array{0: string}, 2: array{0: string}, 3: array{0: string}}
     */
    public static function keyPairStringProvider(): array
    {
        return [
            ['engine=engine_name,var=var_name'],
            ['engine=engine_name, var=var_name'],
            ['var=var_name,engine=engine_name'],
            ['var=var_name, engine=engine_name'],
        ];
    }

    public function testKeyValuePairButNotFound(): void
    {
        $name = new Name('foo=bar');
        $parameter = new ReflectionParameter([FakeCar::class, '__construct'], 'engine');
        $boundName = $name($parameter);
        $this->assertSame(Name::ANY, $boundName);
    }

    public function testKeyValuePairWithDollarPrefix(): void
    {
        $name = new Name('$engine=engine_name,$var=var_name');
        $parameter = new ReflectionParameter([FakeCar::class, '__construct'], 'engine');
        $boundName = $name($parameter);
        $this->assertSame('engine_name', $boundName);
    }

    /**
     * When a parameter has its own named binding, that specific name wins over
     * the catch-all (Name::ANY) entry. This pins the lookup order of
     * names[$parameterName] ?? names[ANY] ?? ANY.
     */
    public function testSpecificNameWinsOverAnyFallback(): void
    {
        $name = new Name(['engine' => 'specific_engine', Name::ANY => 'fallback']);
        $parameter = new ReflectionParameter([FakeCar::class, '__construct'], 'engine');
        $boundName = $name($parameter);
        $this->assertSame('specific_engine', $boundName);
    }

    /**
     * When a parameter has no specific named binding, the Name::ANY entry is
     * used as the fallback (not the empty Name::ANY constant). This pins the
     * middle term of names[$parameterName] ?? names[ANY] ?? ANY.
     */
    public function testAnyEntryUsedAsFallbackForUnnamedParameter(): void
    {
        $name = new Name(['gear' => 'specific_gear', Name::ANY => 'fallback']);
        $parameter = new ReflectionParameter([FakeCar::class, '__construct'], 'engine');
        $boundName = $name($parameter);
        $this->assertSame('fallback', $boundName);
    }
}
