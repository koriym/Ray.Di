<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\MethodInvocation;

/**
 * Module for assisted injection using PHP 8 attributes
 *
 * This module enables assisted injection by delegating to AssistedInjectModule
 * and provides necessary bindings for method invocation context.
 * Use #[Assisted] attribute on method parameters to enable automatic injection.
 */
final class AssistedModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->install(new AssistedInjectModule());
        $this->bind(MethodInvocation::class)->toProvider(MethodInvocationProvider::class)->in(Scope::SINGLETON);
        $this->bind(MethodInvocationProvider::class)->in(Scope::SINGLETON);
    }
}
