<?php

declare(strict_types=1);

namespace Ray\Bindings;

use Ray\Di\Container;

/** Visits the composed container of a module. */
interface ModuleVisitorInterface
{
    public function visit(Container $container): void;
}
