<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use ReflectionParameter;

class InjectionPointTest extends TestCase
{
    /** @var InjectionPointInterface */
    private $ip;

    /** @var ReflectionParameter */
    private $parameter;

    protected function setUp(): void
    {
        $this->parameter = new ReflectionParameter([FakeWalkRobot::class, '__construct'], 'rightLeg');
        $this->ip = new InjectionPoint($this->parameter);
    }

    public function testGetParameter(): void
    {
        $actual = $this->ip->getParameter();
        $this->assertSame($this->parameter, $actual);
    }

    public function testGetMethod(): void
    {
        $actual = $this->ip->getMethod();
        $this->assertSame((string) $this->parameter->getDeclaringFunction(), (string) $actual);
    }

    public function testGetClass(): void
    {
        $actual = $this->ip->getClass();
        $this->assertSame((string) $this->parameter->getDeclaringClass(), (string) $actual);
    }

    public function testGetQualifiers(): void
    {
        $annotations = $this->ip->getQualifiers();
        $this->assertCount(1, $annotations);
        $this->assertInstanceOf(FakeConstant::class, $annotations[0]);
    }

    /**
     * getQualifiers() must return every qualifier annotation on the method, not
     * just the first one. With two qualifier attributes both must be returned.
     */
    public function testGetQualifiersReturnsAllQualifiers(): void
    {
        $parameter = new ReflectionParameter([FakeMultiQualifierConsumer::class, '__construct'], 'engine');
        $ip = new InjectionPoint($parameter);
        $qualifiers = $ip->getQualifiers();
        $this->assertCount(2, $qualifiers);
        $classes = [$qualifiers[0]::class, $qualifiers[1]::class];
        $this->assertContains(FakeLeft::class, $classes);
        $this->assertContains(FakeRight::class, $classes);
    }
}
