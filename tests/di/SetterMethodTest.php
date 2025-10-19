<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use Ray\Di\Exception\Unbound;
use ReflectionMethod;
use ReflectionParameter;

use function spl_object_hash;

class SetterMethodTest extends TestCase
{
    /** @var SetterMethods */
    protected $setterMethods;

    protected function setUp(): void
    {
        $method = new ReflectionMethod(FakeCar::class, 'setTires');
        $setterMethod = new SetterMethod($method, new Name(Name::ANY));
        $this->setterMethods = new SetterMethods([$setterMethod]);
    }

    public function testInvoke(): void
    {
        $container = new Container();
        (new Bind($container, FakeTyreInterface::class))->to(FakeTyre::class);
        $car = new FakeCar(new FakeEngine());
        // setter injection
        $this->setterMethods->__invoke($car, $container);
        $this->assertInstanceOf(FakeTyre::class, $car->frontTyre);
        $this->assertInstanceOf(FakeTyre::class, $car->rearTyre);
        $this->assertNotSame(spl_object_hash($car->frontTyre), spl_object_hash($car->rearTyre));
    }

    public function testUnbound(): void
    {
        $this->expectException(Unbound::class);
        $container = new Container();
        $car = new FakeCar(new FakeEngine());
        $this->setterMethods->__invoke($car, $container);
    }

    public function testAcceptWithUnboundException(): void
    {
        $this->expectException(Unbound::class);
        $method = new ReflectionMethod(FakeCar::class, 'setTires');
        $setterMethod = new SetterMethod($method, new Name(Name::ANY));

        $visitor = new class implements VisitorInterface
        {
            public function visitSetterMethod(string $method, Arguments $arguments): void
            {
                throw new Unbound(FakeTyreInterface::class);
            }

            public function visitDependency(NewInstance $newInstance, ?string $postConstruct, bool $isSingleton): void
            {
            }

            public function visitProvider(Dependency $dependency, string $context, bool $isSingleton): string
            {
                return '';
            }

            /** @param mixed $value */
            public function visitInstance($value): string
            {
                return '';
            }

            public function visitAspectBind(\Ray\Aop\Bind $aopBind): void
            {
            }

            public function visitNewInstance(string $class, SetterMethods $setterMethods, ?Arguments $arguments, ?AspectBind $bind): void
            {
            }

            /** @param array<SetterMethod> $setterMethods */
            public function visitSetterMethods(array $setterMethods): void
            {
            }

            /** @param array<Argument> $arguments */
            public function visitArguments(array $arguments): void
            {
            }

            /** @param mixed $defaultValue */
            public function visitArgument(string $index, bool $isDefaultAvailable, $defaultValue, ReflectionParameter $parameter): void
            {
            }
        };

        $setterMethod->accept($visitor);
    }

    public function testAcceptWithUnboundExceptionOptional(): void
    {
        $method = new ReflectionMethod(FakeCar::class, 'setTires');
        $setterMethod = new SetterMethod($method, new Name(Name::ANY));
        $setterMethod->setOptional();

        $visitor = new class implements VisitorInterface
        {
            public function visitSetterMethod(string $method, Arguments $arguments): void
            {
                throw new Unbound(FakeTyreInterface::class);
            }

            public function visitDependency(NewInstance $newInstance, ?string $postConstruct, bool $isSingleton): void
            {
            }

            public function visitProvider(Dependency $dependency, string $context, bool $isSingleton): string
            {
                return '';
            }

            /** @param mixed $value */
            public function visitInstance($value): string
            {
                return '';
            }

            public function visitAspectBind(\Ray\Aop\Bind $aopBind): void
            {
            }

            public function visitNewInstance(string $class, SetterMethods $setterMethods, ?Arguments $arguments, ?AspectBind $bind): void
            {
            }

            /** @param array<SetterMethod> $setterMethods */
            public function visitSetterMethods(array $setterMethods): void
            {
            }

            /** @param array<Argument> $arguments */
            public function visitArguments(array $arguments): void
            {
            }

            /** @param mixed $defaultValue */
            public function visitArgument(string $index, bool $isDefaultAvailable, $defaultValue, ReflectionParameter $parameter): void
            {
            }
        };

        $result = $setterMethod->accept($visitor);
        $this->assertNull($result);
    }
}
