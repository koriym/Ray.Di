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
