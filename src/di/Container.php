<?php

declare(strict_types=1);

namespace Ray\Di;

use BadMethodCallException;
use Ray\Aop\Compiler;
use Ray\Aop\CompilerInterface;
use Ray\Aop\Pointcut;
use Ray\Di\Exception\CircularDependency;
use Ray\Di\Exception\NoHint;
use Ray\Di\Exception\RenameTargetAlreadyBound;
use Ray\Di\Exception\Unbound;
use Ray\Di\Exception\Untargeted;
use Ray\Di\MultiBinding\MultiBindings;
use ReflectionClass;

use function array_intersect_key;
use function array_keys;
use function array_merge;
use function class_exists;
use function implode;
use function ksort;
use function sprintf;

/**
 * @psalm-import-type DependencyContainer from Types
 * @psalm-import-type PointcutList from Types
 * @psalm-import-type DependencyIndex from Types
 * @psalm-import-type MethodArguments from Types
 * @psalm-import-type InjectableValue from Types
 */
final class Container implements InjectorInterface
{
    /** @var MultiBindings */
    public $multiBindings;

    /** @var DependencyContainer */
    private array $container = [];

    /** @var array<int, Pointcut> */
    private array $pointcuts = [];

    /**
     * Dependency indexes currently being resolved, used to detect circular dependencies
     *
     * Not serialized: resolution state is always empty between getInstance() calls.
     *
     * @var array<string, true>
     */
    private array $resolving = [];

    /**
     * Composition-time binding history
     *
     * Not serialized: the __sleep() whitelist excludes it, so a revived
     * container starts with an empty log (see getBindingLog()).
     */
    private ?BindingLog $log = null;

    /**
     * Module FQCN whose writes are currently being recorded
     *
     * Not serialized: attribution is composition-time state; writes after
     * unserialize() are attributed 'unknown'.
     */
    private string $source = '';

    public function __construct()
    {
        $this->multiBindings = new MultiBindings();
    }

    /**
     * @return list<string>
     */
    public function __sleep()
    {
        return ['container', 'pointcuts', 'multiBindings'];
    }

    /**
     * Set the module FQCN to which subsequent binding writes are attributed
     */
    public function setSource(string $module): void
    {
        $this->source = $module;
    }

    /**
     * Return the composition-time binding log
     *
     * Lazy-initialized so an unserialize()d container — whose whitelist-based
     * __sleep() dropped the log, leaving the property at its default — still
     * returns a usable (empty) log.
     */
    public function getBindingLog(): BindingLog
    {
        if (! isset($this->log)) {
            $this->log = new BindingLog();
        }

        return $this->log;
    }

    /**
     * Add binding to a container
     */
    public function add(Bind $bind): void
    {
        $index = (string) $bind;
        $previous = $this->container[$index] ?? null;
        $dependency = $bind->getBound();
        $dependency->register($this->container, $bind);
        /** @psalm-suppress InvalidArrayAccess -- register()'s @param-out leaves the DependencyContainer alias unexpanded */
        $this->getBindingLog()->register(
            $index,
            (string) $this->container[$index],
            $previous === null ? null : (string) $previous,
            $this->source !== '' ? $this->source : 'unknown'
        );
    }

    /**
     * @internal
     */
    public function setInjectionPoint(InjectionPointInterface $ip): void
    {
        $this->container[InjectionPointInterface::class . '-' . Name::ANY] = new Instance($ip);
    }

    /**
     * Add Pointcut to container
     */
    public function addPointcut(Pointcut $pointcut): void
    {
        $this->pointcuts[] = $pointcut;
    }

    /**
     * {@inheritDoc}
     *
     * @param ''|class-string<T> $interface
     * @param string             $name
     *
     * @return ($interface is '' ? mixed : T)
     *
     * @template T of object
     */
    public function getInstance($interface, $name = Name::ANY)
    {
        /** @psalm-suppress MixedReturnStatement */
        return $this->getDependency($interface . '-' . $name);
    }

    /**
     * Return dependency injected instance
     *
     * @param MethodArguments $params
     *
     * @return mixed
     *
     * @throws Unbound
     */
    public function getInstanceWithArgs(string $interface, array $params)
    {
        $index = $interface . '-';
        if (! isset($this->container[$index])) {
            throw $this->unbound($index);
        }

        $dependency = $this->container[$index];
        if (! $dependency instanceof Dependency) {
            throw new BadMethodCallException($interface);
        }

        return $dependency->injectWithArgs($this, $params);
    }

    /**
     * Return dependency injected instance
     *
     * @param DependencyIndex $index
     *
     * @return mixed
     *
     * @throws Unbound
     */
    public function getDependency(string $index)
    {
        if (! isset($this->container[$index])) {
            throw $this->unbound($index);
        }

        if (isset($this->resolving[$index])) {
            $dependency = $this->container[$index];
            // An already-instantiated singleton satisfies re-entrant requests
            // (e.g. from a @PostConstruct method) with its cached instance,
            // without recursing — not a cycle.
            if ($dependency instanceof Dependency && $dependency->isInstantiated()) {
                return $dependency->inject($this);
            }

            throw new CircularDependency(sprintf("'%s'", implode(' -> ', [...array_keys($this->resolving), $index])));
        }

        $this->resolving[$index] = true;
        try {
            return $this->container[$index]->inject($this);
        } finally {
            unset($this->resolving[$index]);
        }
    }

    /**
     * Rename existing dependency interface + name
     *
     * @throws Unbound                  When no binding exists at the source index.
     * @throws RenameTargetAlreadyBound When a binding already exists at the target index.
     */
    public function move(string $sourceInterface, string $sourceName, string $targetInterface, string $targetName): void
    {
        $sourceIndex = $sourceInterface . '-' . $sourceName;
        if (! isset($this->container[$sourceIndex])) {
            throw $this->unbound($sourceIndex);
        }

        $targetIndex = $targetInterface . '-' . $targetName;
        if ($targetIndex !== $sourceIndex) {
            if (isset($this->container[$targetIndex])) {
                throw new RenameTargetAlreadyBound(sprintf("'%s'", $targetIndex));
            }

            $this->container[$targetIndex] = $this->container[$sourceIndex];
            unset($this->container[$sourceIndex]);
            $this->getBindingLog()->move($sourceIndex, $targetIndex);
        }
    }

    /**
     * Return Unbound exception
     *
     * @param DependencyIndex $index {interface}-{bind name}
     */
    public function unbound(string $index): Untargeted|Unbound
    {
        [$class, $name] = BindingIndex::parse($index);
        if (class_exists($class) && ! (new ReflectionClass($class))->isAbstract()) {
            return new Untargeted($class);
        }

        if ($class === '' && $name === '') {
            return new NoHint();
        }

        return new Unbound(sprintf("'%s-%s'", $class, $name));
    }

    /**
     * Return container
     *
     * @return array<non-empty-string, DependencyInterface>
     * @psalm-return DependencyContainer
     */
    public function getContainer(): array
    {
        return $this->container;
    }

    /**
     * Return pointcuts
     *
     * @return array<int, Pointcut>
     * @psalm-return PointcutList
     */
    public function getPointcuts(): array
    {
        return $this->pointcuts;
    }

    /**
     * Merge container
     *
     * The collision history is recorded first: `+=` lets existing entries win
     * and silently discards the incoming side, so each colliding index is
     * logged as a keep event before the merge makes the discard invisible.
     */
    public function merge(self $container): void
    {
        $otherContainer = $container->getContainer();
        $collidingIndexes = array_keys(array_intersect_key($this->container, $otherContainer));
        $keptDependencies = [];
        $discardedDependencies = [];
        foreach ($collidingIndexes as $index) {
            $keptDependencies[$index] = (string) $this->container[$index];
            $discardedDependencies[$index] = (string) $otherContainer[$index];
        }

        $this->getBindingLog()->merge($container->getBindingLog(), $collidingIndexes, $keptDependencies, $discardedDependencies);

        $this->multiBindings->merge($container->multiBindings);
        $this->container += $otherContainer;
        $this->pointcuts = array_merge($this->pointcuts, $container->getPointcuts());
    }

    /**
     * Weave aspects to all dependency in container
     */
    public function weaveAspects(CompilerInterface $compiler): void
    {
        if ($this->pointcuts === []) {
            return;
        }

        foreach ($this->container as $dependency) {
            if ($dependency instanceof Dependency) {
                $dependency->weaveAspects($compiler, $this->pointcuts);
            }
        }
    }

    /**
     * Weave aspect to single dependency
     */
    public function weaveAspect(Compiler $compiler, Dependency $dependency): self
    {
        $dependency->weaveAspects($compiler, $this->pointcuts);

        return $this;
    }

    /**
     * @param callable(DependencyInterface, string): DependencyInterface $f
     */
    public function map(callable $f): void
    {
        foreach ($this->container as $key => &$index) {
            $index = $f($index, $key);
        }
    }

    public function sort(): void
    {
        ksort($this->container);
    }
}
