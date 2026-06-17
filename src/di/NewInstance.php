<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\Bind as AopBind;
use Ray\Aop\WeavedInterface;
use ReflectionClass;
use Stringable;

use function assert;

/**
 * @psalm-import-type MethodArguments from Types
 */
final class NewInstance implements Stringable
{
    /** @var class-string */
    private string $class;

    /** @var SetterMethods */
    private $setterMethods;
    private ?Arguments $arguments = null;
    private ?AspectBind $bind = null;

    /**
     * @phpstan-param ReflectionClass<object> $class
     */
    public function __construct(
        ReflectionClass $class,
        SetterMethods $setterMethods,
        ?Name $constructorName = null
    ) {
        $constructorName = $constructorName ?: new Name(Name::ANY);
        $this->class = $class->getName();
        $constructor = $class->getConstructor();
        if ($constructor) {
            $this->arguments = new Arguments($constructor, $constructorName);
        }

        $this->setterMethods = $setterMethods;
    }

    public function __invoke(Container $container): object
    {
        /** @var class-string $class */
        $class = $this->class;
        /** @psalm-suppress MixedMethodCall */
        $instance = $this->arguments instanceof Arguments ? new $class(...$this->arguments->inject($container)) : new $class();

        return $this->postNewInstance($container, $instance);
    }

    /**
     * @return class-string
     */
    public function __toString(): string
    {
        return $this->class;
    }

    /**
     * @param MethodArguments $params
     */
    public function newInstanceArgs(Container $container, array $params): object
    {
        /** @var class-string $class */
        $class = $this->class;
        /** @psalm-suppress MixedMethodCall */
        $instance = new $class(...$params);

        return $this->postNewInstance($container, $instance);
    }

    /**
     * @param class-string $class
     */
    public function weaveAspects(string $class, AopBind $bind): void
    {
        $this->class = $class;
        $this->bind = new AspectBind($bind);
    }

    public function accept(VisitorInterface $visitor): void
    {
        $visitor->visitNewInstance(
            $this->class,
            $this->setterMethods,
            $this->arguments,
            $this->bind
        );
    }

    private function postNewInstance(Container $container, object $instance): object
    {
        $this->enableAop($instance, $container);

        // setter injection
        ($this->setterMethods)($instance, $container);

        return $instance;
    }

    public function enableAop(object $instance, Container $container): void
    {
        if (! $this->bind instanceof AspectBind) {
            return;
        }

        assert($instance instanceof WeavedInterface);

        $instance->_setBindings($this->bind->inject($container));  // Ray.Aop ^2.18
    }
}
