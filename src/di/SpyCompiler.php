<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\BindInterface;
use Ray\Aop\CompilerInterface;
use Ray\Di\Exception\SpyCompilerNotInstantiable;

use function array_keys;
use function implode;
use function is_object;
use function method_exists;
use function sprintf;

/** @codeCoverageIgnore */
final class SpyCompiler implements CompilerInterface
{
    /**
     * {@inheritDoc}
     *
     * @phpstan-return never
     *
     * @psalm-suppress InvalidReturnType
     * @template T of object
     */
    public function newInstance(string $class, array $args, BindInterface $bind)
    {
        // never called
        throw new SpyCompilerNotInstantiable($class);
    }

    /**
     * Return "logging" class name
     *
     * Dummy classes are used for logging and don't really exist.
     * So the code breaks the QA rules as shown below.
     * NOTE: psalm-suppress is acceptable here for dummy/logging infrastructure
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function compile(string $class, BindInterface $bind): string
    {
        if ($this->hasNoBinding($class, $bind)) {
            return $class;
        }

        return $class . $this->getInterceptors($bind); // @phpstan-ignore-line
    }

    /** @param class-string $class */
    private function hasNoBinding(string $class, BindInterface $bind): bool
    {
        $hasMethod = $this->hasBoundMethod($class, $bind);

        return ! $bind->getBindings() && ! $hasMethod;
    }

    /** @param class-string $class */
    private function hasBoundMethod(string $class, BindInterface $bind): bool
    {
        $bindingMethods = array_keys($bind->getBindings());
        $hasMethod = false;
        foreach ($bindingMethods as $bindingMethod) {
            if (method_exists($class, $bindingMethod)) {
                $hasMethod = true;
            }
        }

        return $hasMethod;
    }

    private function getInterceptors(BindInterface $bind): string
    {
        $bindings = $bind->getBindings();
        if (! $bindings) {
            return ''; // @codeCoverageIgnore
        }

        $log = ' (aop)';
        foreach ($bindings as $method => $interceptors) {
            $names = [];
            // Ray.Aop's MethodInterceptors declares instance-only, but class-strings flow through too
            /** @var array<class-string|object> $interceptors */
            foreach ($interceptors as $interceptor) {
                $names[] = is_object($interceptor) ? $interceptor::class : $interceptor;
            }

            $log .= sprintf(
                ' +%s(%s)',
                $method,
                implode(', ', $names)
            );
        }

        return $log;
    }
}
