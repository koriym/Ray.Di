<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\Bind as AopBind;
use Ray\Aop\CompilerInterface;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\WeavedInterface;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

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

    /** @return array<string> */
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
     * Return the AOP-aware description without mutating this dependency
     *
     * @param PointcutList $pointcuts
     *
     * @internal
     */
    public function toStringWithAspects(SpyCompiler $compiler, array $pointcuts): string
    {
        $aspect = $this->compileAspects($compiler, $pointcuts);
        if ($aspect === null) {
            return (string) $this;
        }

        return sprintf('(dependency) %s', $aspect[0]);
    }

    /**
     * {@inheritDoc}
     */
    public function register(array &$container, Bind $bind): void
    {
        $container[(string) $bind] = $bind->getBound();
    }

    /**
     * {@inheritDoc}
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
            try {
                $instance->{$this->postConstruct}();
            } catch (Throwable $e) {
                // Roll back the singleton cache so the next resolution rebuilds
                // instead of returning this half-initialized instance. The cache
                // is committed before PostConstruct on purpose (to let a singleton
                // resolve itself from its own lifecycle method); we only unwind it
                // when PostConstruct actually fails.
                if ($this->isSingleton) {
                    $this->instance = null;
                    $this->isInstantiated = false;
                }

                throw $e;
            }
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
            try {
                $instance->{$this->postConstruct}();
            } catch (Throwable $e) {
                if ($this->isSingleton) {
                    $this->instance = null;
                    $this->isInstantiated = false;
                }

                throw $e;
            }
        }

        return $instance;
    }

    /**
     * {@inheritDoc}
     */
    public function setScope($scope): void
    {
        if ($scope === Scope::SINGLETON) {
            $this->isSingleton = true;
        }
    }

    /** @param PointcutList $pointcuts */
    public function weaveAspects(CompilerInterface $compiler, array $pointcuts): void
    {
        $aspect = $this->compileAspects($compiler, $pointcuts);
        if ($aspect === null) {
            return;
        }

        [$class, $bind] = $aspect;
        $this->newInstance->weaveAspects($class, $bind);
    }

    /**
     * @param PointcutList $pointcuts
     *
     * @return array{class-string, AopBind}|null
     */
    private function compileAspects(CompilerInterface $compiler, array $pointcuts): ?array
    {
        if ($pointcuts === []) {
            return null;
        }

        $class = (string) $this->newInstance;
        $reflection = new ReflectionClass($class);
        if ($reflection->isFinal()) {
            return null;
        }

        $isInterceptor = $reflection->implementsInterface(MethodInterceptor::class);
        $isWeaved = $reflection->implementsInterface(WeavedInterface::class);
        if ($isInterceptor || $isWeaved) {
            return null;
        }

        $bind = new AopBind();
        $bind->bind($class, $pointcuts);
        if (! $bind->getBindings()) {
            return null;
        }

        return [$compiler->compile($class, $bind), $bind];
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
