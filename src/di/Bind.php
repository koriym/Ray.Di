<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\MethodInterceptor;
use Ray\Aop\ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Stringable;

use function assert;
use function class_exists;
use function interface_exists;

/**
 * @psalm-import-type BindableInterface from Types
 * @psalm-import-type BindingName from Types
 * @psalm-import-type ParameterNameMapping from Types
 */
final class Bind implements Stringable
{
    private string $name = Name::ANY;
    private DependencyInterface $bound;
    private readonly BindValidator $validate;

    /** @var ?Untarget */
    private $untarget;

    /**
     * @param Container         $container dependency container
     * @param BindableInterface $interface interface or concrete class name
     * @param string            $source    FQCN of the module (or Injector) that created this binding
     * @phpstan-param class-string<MethodInterceptor>|string $interface
     */
    public function __construct(
        private readonly Container $container,
        private readonly string $interface,
        private readonly string $source = ''
    ) {
        $this->validate = new BindValidator();
        $bindUntarget = class_exists($this->interface) && ! (new \ReflectionClass($this->interface))->isAbstract() && ! $this->isRegistered($this->interface);
        $this->bound = new NullDependency();
        if ($bindUntarget) {
            /** @var class-string $interface */
            $interface = $this->interface;
            $this->untarget = new Untarget($interface);

            return;
        }

        $this->validate->constructor($this->interface);
    }

    public function __destruct()
    {
        if ($this->untarget) {
            ($this->untarget)($this->container, $this);
            $this->untarget = null;
        }
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->interface . '-' . $this->name;
    }

    /**
     * Set dependency name
     *
     * @param BindingName $name
     */
    public function annotatedWith(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Bind to class
     *
     * @param class-string $class
     */
    public function to(string $class): self
    {
        $this->untarget = null;
        $refClass = $this->validate->to($this->interface, $class);
        $this->bound = (new DependencyFactory())->newAnnotatedDependency($refClass);
        $this->container->add($this);

        return $this;
    }

    /**
     * Bind to constructor
     *
     * @param class-string<T>             $class class name
     * @param ParameterNameMapping|string $name  "varName=bindName,..." or [$varName => $bindName, $varName => $bindName...]
     *
     * @throws ReflectionException
     *
     * @template T of object
     */
    public function toConstructor(string $class, string|array $name, ?InjectionPoints $injectionPoints = null, ?string $postConstruct = null): self
    {
        $this->untarget = null;
        $postConstructRef = $postConstruct !== null ? new ReflectionMethod($class, $postConstruct) : null;
        /** @var ReflectionClass<object> $reflection */
        $reflection = new ReflectionClass($class);
        $this->bound = (new DependencyFactory())->newToConstructor($reflection, $name, $injectionPoints, $postConstructRef);
        $this->container->add($this);

        return $this;
    }

    /**
     * Bind to provider
     *
     * @phpstan-param class-string $provider
     */
    public function toProvider(string $provider, string $context = ''): self
    {
        $this->untarget = null;
        $refClass = $this->validate->toProvider($provider);
        $this->bound = (new DependencyFactory())->newProvider($refClass, $context);
        $this->container->add($this);

        return $this;
    }

    /**
     * Bind to instance
     *
     * @param mixed $instance
     */
    public function toInstance($instance): self
    {
        $this->untarget = null;
        $this->bound = new Instance($instance);
        $this->container->add($this);

        return $this;
    }

    /**
     * Bind to NullObject
     */
    public function toNull(): self
    {
        $this->untarget = null;
        assert(interface_exists($this->interface));
        $this->bound = new NullObjectDependency($this->interface);
        $this->container->add($this);

        return $this;
    }

    /**
     * Set scope
     */
    public function in(string $scope): self
    {
        if ($this->bound instanceof Dependency || $this->bound instanceof DependencyProvider || $this->bound instanceof NullDependency) {
            $this->bound->setScope($scope);
        }

        if ($this->untarget) {
            $this->untarget->setScope($scope);
        }

        return $this;
    }

    public function getBound(): DependencyInterface
    {
        return $this->bound;
    }

    /**
     * FQCN of the module (or Injector) that created this binding, '' when unknown
     */
    public function getSource(): string
    {
        return $this->source;
    }

    public function setBound(DependencyInterface $bound): void
    {
        $this->bound = $bound;
    }

    private function isRegistered(string $interface): bool
    {
        return isset($this->container->getContainer()[$interface . '-' . Name::ANY]);
    }
}
