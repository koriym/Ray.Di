<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionParameter;

use function assert;
use function serialize;
use function unserialize;

class ArgumentTest extends TestCase
{
    /** @var Argument */
    protected $argument;

    protected function setUp(): void
    {
        $this->argument = new Argument(new ReflectionParameter([FakeCar::class, '__construct'], 'engine'), Name::ANY);
    }

    public function testToString(): void
    {
        $this->assertSame('Ray\Di\FakeEngineInterface-' . Name::ANY, (string) $this->argument);
    }

    public function testToStringScalar(): void
    {
        $argument = new Argument(new ReflectionParameter([FakeInternalTypes::class, 'stringId'], 'id'), Name::ANY);
        $this->assertSame('-' . Name::ANY, (string) $argument);
    }

    public function testSerializable(): void
    {
        $argument = unserialize(serialize(new Argument(new ReflectionParameter([FakeInternalTypes::class, 'stringId'], 'id'), Name::ANY)));
        assert($argument instanceof Argument);
        $class = $argument->get()->getDeclaringFunction();
        $this->assertInstanceOf(ReflectionMethod::class, $class);
    }

    /**
     * A required (non-optional, no default) parameter has no default available.
     */
    public function testRequiredParameterHasNoDefault(): void
    {
        $argument = new Argument(new ReflectionParameter([FakeCar::class, '__construct'], 'engine'), Name::ANY);
        $this->assertFalse($argument->isDefaultAvailable());
    }

    /**
     * A parameter with an explicit default value exposes that default.
     */
    public function testParameterWithDefaultValue(): void
    {
        $argument = new Argument(new ReflectionParameter([FakeHandleProvider::class, '__construct'], 'logo'), Name::ANY);
        $this->assertTrue($argument->isDefaultAvailable());
        $this->assertSame('nardi', $argument->getDefaultValue());
    }

    /**
     * A variadic parameter is optional yet has no retrievable default value.
     *
     * This pins the `isDefaultValueAvailable() || isOptional()` contract: a
     * variadic is optional (so the default must be considered available) even
     * though isDefaultValueAvailable() is false. Replacing the OR with an AND
     * would wrongly report the default as unavailable.
     */
    public function testVariadicParameterIsDefaultAvailable(): void
    {
        $argument = new Argument(new ReflectionParameter([FakeVariadicConstructor::class, '__construct'], 'engines'), Name::ANY);
        $this->assertTrue($argument->isDefaultAvailable());
    }
}
