<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\PostConstruct;

/**
 * Singleton that resolves a class depending back on itself from @PostConstruct
 *
 * This works only because the singleton instance is cached before the
 * PostConstruct method runs, which is the supported way to break a
 * dependency cycle.
 */
class FakeWarmup
{
    public ?FakeWarmupDependent $dependent = null;

    public function __construct(
        public InjectorInterface $injector
    ) {
    }

    #[PostConstruct]
    public function warmUp(): void
    {
        $this->dependent = $this->injector->getInstance(FakeWarmupDependent::class);
    }
}
