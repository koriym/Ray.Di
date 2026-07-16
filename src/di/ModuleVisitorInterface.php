<?php

declare(strict_types=1);

namespace Ray\Di;

/** Visits a module's composed container. */
interface ModuleVisitorInterface
{
    public function visit(Container $container): void;
}
