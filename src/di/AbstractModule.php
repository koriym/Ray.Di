<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\AbstractMatcher;
use Ray\Aop\Matcher;
use Ray\Aop\Pointcut;
use Ray\Aop\PriorityPointcut;
use Ray\Bindings\ModuleVisitorInterface;
use Ray\Di\Exception\RenameTargetAlreadyBound;
use Stringable;

use function assert;
use function class_exists;
use function interface_exists;
use function sprintf;

/**
 * @psalm-import-type BindableInterface from Types
 * @psalm-import-type PointcutList from Types
 * @psalm-import-type InterceptorClassList from Types
 */
abstract class AbstractModule implements Stringable
{
    /** @var Matcher */
    protected $matcher;

    /**
     * @var ?AbstractModule
     * @deprecated Unused since rename() now operates on getContainer().
     */
    protected $lastModule;
    private ?Container $container = null;

    /**
     * Renames deferred until the constructor-chained module arrives
     *
     * @var list<array{string, string, string, string}> [$interface, $newName, $sourceName, $targetInterface]
     */
    private array $pendingRenames = [];
    private bool $isConfiguring = false;

    public function __construct(
        ?self $module = null
    ) {
        /** @psalm-suppress DeprecatedProperty kept for BC */
        $this->lastModule = $module;
        $this->container = new Container();
        $this->matcher = new Matcher();
        $this->isConfiguring = true;
        $this->configure();
        // Merged after configure() so that everything configure() declared —
        // bind(), install()ed bindings, pointcuts — takes priority over the
        // chained module's, as in `new ProdModule(new AppModule())`.
        if ($module instanceof self) {
            $this->applyPendingRenamesTo($module->getContainer());
            $this->getContainer()->merge($module->getContainer());
        }

        $this->applyPendingRenames();
    }

    public function __toString(): string
    {
        return (new ModuleString())($this->getContainer(), $this->getContainer()->getPointcuts());
    }

    /** Visit the module after its bindings have been composed. */
    public function accept(ModuleVisitorInterface $visitor): void
    {
        $visitor->visit($this->getContainer());
    }

    /**
     * Install module
     */
    public function install(self $module): void
    {
        $this->getContainer()->merge($module->getContainer());
    }

    /**
     * Override module
     */
    public function override(self $module): void
    {
        $module->getContainer()->merge($this->getContainer());
        $this->container = $module->getContainer();
    }

    /**
     * Return activated container
     */
    public function getContainer(): Container
    {
        if ($this->container === null) {
            $this->activate();
        }

        assert($this->container instanceof Container);

        return $this->container;
    }

    /**
     * Bind interceptor
     *
     * @param InterceptorClassList $interceptors
     */
    public function bindInterceptor(AbstractMatcher $classMatcher, AbstractMatcher $methodMatcher, array $interceptors): void
    {
        $pointcut = new Pointcut($classMatcher, $methodMatcher, $interceptors);
        $this->getContainer()->addPointcut($pointcut);
        foreach ($interceptors as $interceptor) {
            if (class_exists($interceptor)) {
                (new Bind($this->getContainer(), $interceptor, static::class))->to($interceptor)->in(Scope::SINGLETON);

                continue;
            }

            assert(interface_exists($interceptor));
            (new Bind($this->getContainer(), $interceptor, static::class))->in(Scope::SINGLETON);
        }
    }

    /**
     * Bind interceptor early
     *
     * @param InterceptorClassList $interceptors
     */
    public function bindPriorityInterceptor(AbstractMatcher $classMatcher, AbstractMatcher $methodMatcher, array $interceptors): void
    {
        $pointcut = new PriorityPointcut($classMatcher, $methodMatcher, $interceptors);
        $this->getContainer()->addPointcut($pointcut);
        foreach ($interceptors as $interceptor) {
            (new Bind($this->getContainer(), $interceptor, static::class))->to($interceptor)->in(Scope::SINGLETON);
        }
    }

    /**
     * Rename a binding
     *
     * Renames an existing binding from $sourceName to $newName, optionally
     * moving it to a different interface. Works on the module's own container,
     * so bindings introduced via constructor chaining, install(), or override()
     * are all reachable.
     *
     * A binding already composed when rename() runs — own bind() or install() —
     * moves on the spot, so a later bind() to the vacated slot decorates it.
     * Inside configure() a source that has not composed yet arrives with the
     * constructor-chained module, so the rename is applied to that module's
     * bindings when composition completes; the exceptions below then surface
     * from the constructor.
     *
     * @param string $interface       Interface
     * @param string $newName         New binding name
     * @param string $sourceName      Original binding name
     * @param string $targetInterface Target interface to move the binding to (default: same as $interface)
     *
     * @throws Exception\Unbound                 When no binding exists at $interface-$sourceName.
     * @throws Exception\RenameTargetAlreadyBound When a binding already exists at the target index.
     */
    public function rename(string $interface, string $newName, string $sourceName = Name::ANY, string $targetInterface = ''): void
    {
        $targetInterface = $targetInterface ?: $interface;
        if ($this->isConfiguring && ! $this->hasBinding($interface, $sourceName)) {
            $this->pendingRenames[] = [$interface, $newName, $sourceName, $targetInterface];

            return;
        }

        $this->getContainer()->move($interface, $sourceName, $targetInterface, $newName);
    }

    /**
     * Configure binding
     *
     * @return void
     *
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    abstract protected function configure();

    /**
     * Bind interface
     *
     * @param BindableInterface $interface
     */
    protected function bind(string $interface = ''): Bind
    {
        return new Bind($this->getContainer(), $interface, static::class);
    }

    /**
     * Activate bindings
     */
    private function activate(): void
    {
        $this->container = new Container();
        $this->matcher = new Matcher();
        $this->isConfiguring = true;
        $this->configure();
        $this->applyPendingRenames();
    }

    private function hasBinding(string $interface, string $name): bool
    {
        return isset($this->getContainer()->getContainer()[$interface . '-' . $name]);
    }

    /**
     * Apply pending renames whose source arrived in the given container, consuming them
     *
     * The move happens before the merge, so the vacated slot stays free for
     * whatever configure() declared there.
     */
    private function applyPendingRenamesTo(Container $container): void
    {
        $remaining = [];
        foreach ($this->pendingRenames as $rename) {
            [$interface, $newName, $sourceName, $targetInterface] = $rename;
            if (! isset($container->getContainer()[$interface . '-' . $sourceName])) {
                $remaining[] = $rename;

                continue;
            }

            if ($this->hasBinding($targetInterface, $newName)) {
                throw new RenameTargetAlreadyBound(sprintf("'%s-%s'", $targetInterface, $newName));
            }

            $container->move($interface, $sourceName, $targetInterface, $newName);
        }

        $this->pendingRenames = $remaining;
    }

    private function applyPendingRenames(): void
    {
        $this->isConfiguring = false;
        foreach ($this->pendingRenames as [$interface, $newName, $sourceName, $targetInterface]) {
            $this->getContainer()->move($interface, $sourceName, $targetInterface, $newName);
        }

        $this->pendingRenames = [];
    }
}
