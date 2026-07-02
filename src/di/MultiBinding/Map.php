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
use function is_scalar;
use function sprintf;

/**
 * @template T
 * @implements ArrayAccess<array-key, T>
 * @implements IteratorAggregate<string, mixed>
 */
final class Map implements IteratorAggregate, ArrayAccess, Countable
{
    /**
     * @param array<array-key, LazyInterface> $lazies
     */
    public function __construct(private array $lazies, private readonly InjectorInterface $injector)
    {
    }

    /**
     * @param array-key $offset
     *
     * @codeCoverageIgnore
     */
    #[ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->lazies);
    }

    /**
     * @param array-key $offset
     *
     * @return T
     *
     * @codeCoverageIgnore
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        /** @var T $instance */
        $instance = ($this->lazies[$offset])($this->injector);

        return $instance;
    }

    /**
     * @param array-key $offset
     * @param mixed     $value
     *
     * @return never
     *
     * @codeCoverageIgnore
     */
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        unset($value);

        throw new ReadOnlyMapAccess(sprintf('Cannot set offset "%s" on a read-only Map', $this->offsetToString($offset)));
    }

    /**
     * @param array-key $offset
     *
     * @return never
     *
     * @codeCoverageIgnore
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        throw new ReadOnlyMapAccess(sprintf('Cannot unset offset "%s" on a read-only Map', $this->offsetToString($offset)));
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

    /**
     * @param mixed $offset array-key at the type level, but ArrayAccess allows null (e.g. `$map[] = $value`)
     */
    private function offsetToString($offset): string
    {
        if ($offset === null) {
            return 'null';
        }

        if (is_scalar($offset)) {
            return (string) $offset;
        }

        return ''; // @codeCoverageIgnore
    }
}
