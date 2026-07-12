<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\Bind as AopBind;
use Ray\Aop\CompilerInterface;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\WeavedInterface;
use ReflectionClass;
use ReflectionMethod;

use function assert;
use function method_exists;
use function sprintf;

/**
 * @psalm-import-type MethodArguments from Types
 * @psalm-import-type PointcutList from Types
 */
final class Dependency implements DependencyInterface, AcceptInterface
{
    /** @var NewInstance */
    private $newInstance;

    /** @var ?string */
    private $postConstruct;
    private bool $isSingleton = false;
    private bool $isInstantiated = false;

    /** @var ?mixed */
    private $instance;

    public function __construct(NewInstance $newInstance, ?ReflectionMethod $postConstruct = null)
    {
        $this->newInstance = $newInstance;
        $this->postConstruct = $postConstruct->name ?? null;
    }

    /**
     * @return array<string>
     */
    public function __sleep()
    {
        return ['newInstance', 'postConstruct', 'isSingleton'];
    }

    public function __toString(): string
    {
        return sprintf(
            '(dependency) %s',
            (string) $this->newInstance
        );
    }

    /**
     * {@inheritdoc}
     */
    public function register(array &$container, Bind $bind): void
    {
        $container[(string) $bind] = $bind->getBound();
    }

    /**
     * {@inheritdoc}
     */
    public function inject(Container $container)
    {
        // singleton ?
        if ($this->isSingleton && $this->isInstantiated) {
            return $this->instance;
        }

        // create dependency injected instance
        $instance = ($this->newInstance)($container);
        if ($this->isSingleton) {
            $this->instance = $instance;
            $this->isInstantiated = true;
        }

        // @PostConstruct
        if ($this->postConstruct !== null) {
            assert(method_exists($instance, $this->postConstruct));
            $instance->{$this->postConstruct}();
        }

        return $instance;
    }

    /**
     * @param MethodArguments $params
     *
     * @return mixed
     */
    public function injectWithArgs(Container $container, array $params)
    {
        // singleton ?
        if ($this->isSingleton && $this->isInstantiated) {
            return $this->instance;
        }

        // create dependency injected instance
        $instance = $this->newInstance->newInstanceArgs($container, $params);
        if ($this->isSingleton) {
            $this->instance = $instance;
            $this->isInstantiated = true;
        }

        // @PostConstruct
        if ($this->postConstruct !== null) {
            assert(method_exists($instance, $this->postConstruct));
            $instance->{$this->postConstruct}();
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function setScope($scope): void
    {
        if ($scope === Scope::SINGLETON) {
            $this->isSingleton = true;
        }
    }

    /**
     * @param PointcutList $pointcuts
     */
    public function weaveAspects(CompilerInterface $compiler, array $pointcuts): void
    {
        $bind = $this->aopBind($pointcuts);
        if (! $bind instanceof AopBind) {
            return;
        }

        $className = (string) $this->newInstance;
        $class = $compiler->compile($className, $bind);
        $this->newInstance->weaveAspects($class, $bind);
    }

    /**
     * Read-only counterpart of weaveAspects() for introspection
     *
     * Returns the same '(dependency) ClassName (aop) +method(...)' string that
     * stringifying a spy-woven dependency produces, but WITHOUT mutating $this
     * — so callers (ModuleString) no longer need to deep-copy the container to
     * protect it from the spy weave.
     *
     * @param PointcutList $pointcuts
     */
    public function describe(array $pointcuts): string
    {
        $className = (string) $this->newInstance;
        $bind = $this->aopBind($pointcuts);
        if ($bind instanceof AopBind) {
            $className = (new SpyCompiler())->compile($className, $bind);
        }

        return sprintf('(dependency) %s', $className);
    }

    /**
     * Match pointcuts against this dependency's class, read-only
     *
     * @param PointcutList $pointcuts
     *
     * @return ?AopBind the matched bindings, or null when nothing is intercepted
     */
    private function aopBind(array $pointcuts): ?AopBind
    {
        $className = (string) $this->newInstance;
        $reflection = new ReflectionClass($className);
        if ($reflection->isFinal()) {
            return null;
        }

        if ($reflection->implementsInterface(MethodInterceptor::class) || $reflection->implementsInterface(WeavedInterface::class)) {
            return null;
        }

        $bind = new AopBind();
        $bind->bind($className, $pointcuts);

        return $bind->getBindings() ? $bind : null;
    }

    /** @inheritDoc */
    public function accept(VisitorInterface $visitor)
    {
        return $visitor->visitDependency(
            $this->newInstance,
            $this->postConstruct,
            $this->isSingleton
        );
    }

    public function isSingleton(): bool
    {
        return $this->isSingleton;
    }

    public function isInstantiated(): bool
    {
        return $this->isInstantiated;
    }
}
