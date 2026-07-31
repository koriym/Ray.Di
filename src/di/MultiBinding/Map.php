<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use ArrayAccess;
use Countable;
use Generator;
use Iterator;
use IteratorAggregate;
use Ray\Di\Exception\ReadOnlyMapAccess;
use Ray\Di\InjectorInterface;
use ReturnTypeWillChange;

use function array_key_exists;
use function count;

/**
 * @template T
 * @implements ArrayAccess<array-key, T>
 * @implements IteratorAggregate<string, mixed>
 */
final class Map implements IteratorAggregate, ArrayAccess, Countable
{
    /** @param array<array-key, LazyInterface> $lazies */
    public function __construct(private array $lazies, private readonly InjectorInterface $injector)
    {
    }

    /** @param array-key $offset */
    #[ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->lazies);
    }

    /**
     * @param array-key $offset
     *
     * @return T
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        /** @var T $instance */
        $instance = ($this->lazies[$offset])($this->injector);

        return $instance;
    }

    /**
     * @param array-key|null $offset null when called via `$map[] = $value`
     * @param mixed          $value
     *
     * @return never
     */
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        unset($value);

        throw new ReadOnlyMapAccess((string) $offset);
    }

    /**
     * @param array-key|null $offset
     *
     * @return never
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        throw new ReadOnlyMapAccess((string) $offset);
    }

    /** @return Generator<array-key, T, void, void> */
    public function getIterator(): Iterator
    {
        foreach ($this->lazies as $key => $lazy) {
            /** @var T $object */
            $object = ($lazy)($this->injector);

            yield $key => $object;
        }
    }

    public function count(): int
    {
        return count($this->lazies);
    }
}
