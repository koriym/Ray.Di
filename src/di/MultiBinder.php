<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\MultiBinding\LazyInstance;
use Ray\Di\MultiBinding\LazyInterface;
use Ray\Di\MultiBinding\LazyProvider;
use Ray\Di\MultiBinding\LazyTo;
use Ray\Di\MultiBinding\MultiBindings;

/**
 * @psalm-import-type BindableInterface from Types
 * @psalm-import-type LazyBindingList from Types
 */
final class MultiBinder
{
    private readonly Container $container;

    /** @var MultiBindings */
    private $multiBindings;
    private ?string $key = null;

    private function __construct(AbstractModule $module, private readonly string $interface)
    {
        $this->container = $module->getContainer();
        $this->multiBindings = $this->container->multiBindings;
        $this->declareSet();
        $this->container->add(
            (new Bind($this->container, MultiBindings::class))->toInstance($this->multiBindings)
        );
    }

    public static function newInstance(AbstractModule $module, string $interface): self
    {
        return new self($module, $interface);
    }

    public function addBinding(?string $key = null): self
    {
        $this->key = $key;

        return $this;
    }

    public function setBinding(?string $key = null): self
    {
        $this->multiBindings->offsetUnset($this->interface);
        $this->key = $key;

        return $this;
    }

    /** @param class-string $class */
    public function to(string $class): void
    {
        $this->bind(new LazyTo($class), $this->key);
    }

    /**
     * @param class-string<ProviderInterface<T>> $provider
     *
     * @template T of mixed
     */
    public function toProvider(string $provider): void
    {
        $this->bind(new LazyProvider($provider), $this->key);
    }

    /** @param mixed $instance */
    public function toInstance($instance): void
    {
        $this->bind(new LazyInstance($instance), $this->key);
    }

    /**
     * Declare the set, so that "declared with no member" is a state the store
     * can represent and MapProvider can inject as an empty Map
     */
    private function declareSet(): void
    {
        if ($this->multiBindings->offsetExists($this->interface)) {
            return;
        }

        $this->multiBindings->offsetSet($this->interface, []);
    }

    private function bind(LazyInterface $lazy, ?string $key): void
    {
        // setBinding() removes the key, so it may be absent here
        $bindings = [];
        if ($this->multiBindings->offsetExists($this->interface)) {
            $bindings = $this->multiBindings->offsetGet($this->interface);
        }

        if ($key === null) {
            $bindings[] = $lazy;
            $this->multiBindings->offsetSet($this->interface, $bindings);

            return;
        }

        $bindings[$key] = $lazy;
        $this->multiBindings->offsetSet($this->interface, $bindings);
    }
}
