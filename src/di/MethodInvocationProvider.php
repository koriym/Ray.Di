<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\MethodInvocation;
use Ray\Di\Exception\MethodInvocationNotAvailable;

use function array_pop;
use function count;
use function end;

/**
 * Per-coroutine LIFO stack of in-flight method invocations
 *
 * A completed or abandoned interception must not leave the finished invocation
 * resolvable, and a nested interception must restore the enclosing one.
 *
 * @implements ProviderInterface<MethodInvocation>
 */
final class MethodInvocationProvider implements ProviderInterface
{
    /** @var array<int, list<MethodInvocation<object>>> */
    private array $invocations = [];

    /** @param MethodInvocation<object> $invocation */
    public function set(MethodInvocation $invocation): void
    {
        $cid = CoroutineContext::id();
        $this->invocations[$cid][] = $invocation;
    }

    public function pop(): void
    {
        $cid = CoroutineContext::id();
        array_pop($this->invocations[$cid]);
        if (count($this->invocations[$cid]) === 0) {
            unset($this->invocations[$cid]);
        }
    }

    /** @return MethodInvocation<object> */
    public function get(): MethodInvocation
    {
        $stack = $this->invocations[CoroutineContext::id()] ?? [];
        $invocation = end($stack);
        if ($invocation === false) {
            throw new MethodInvocationNotAvailable();
        }

        return $invocation;
    }
}
