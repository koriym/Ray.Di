<?php

declare(strict_types=1);

namespace Ray\Di;

use function implode;
use function sort;
use function sprintf;

use const PHP_EOL;

/**
 * @psalm-import-type PointcutList from Types
 */
final class ModuleString
{
    /**
     * @param PointcutList $pointcuts
     */
    public function __invoke(Container $container, array $pointcuts): string
    {
        $log = [];
        foreach ($container->getContainer() as $dependencyIndex => $dependency) {
            // Dependency::describe() computes the AOP annotation read-only, so —
            // unlike the former spy weave — the container is neither mutated nor
            // serialize()-deep-copied to protect it (which also threw on
            // unserializable instances such as closures bound via toInstance()).
            $log[] = sprintf(
                '%s => %s',
                $dependencyIndex,
                $dependency instanceof Dependency ? $dependency->describe($pointcuts) : (string) $dependency
            );
        }

        sort($log);

        return implode(PHP_EOL, $log);
    }
}
