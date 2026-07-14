<?php

declare(strict_types=1);

namespace Ray\Di;

use function implode;
use function sort;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;

use const PHP_EOL;

/** @psalm-import-type PointcutList from Types */
final class ModuleString
{
    /** @param PointcutList $pointcuts */
    public function __invoke(Container $container, array $pointcuts): string
    {
        $log = [];
        /** @var array<string, string> $dependencyStrings */
        $dependencyStrings = [];
        $spy = new SpyCompiler();
        foreach ($container->getContainer() as $dependencyIndex => $dependency) {
            $dependencyString = (string) $dependency;
            if ($dependency instanceof Dependency) {
                if (! isset($dependencyStrings[$dependencyString])) {
                    $dependencyStrings[$dependencyString] = $dependency->toStringWithAspects($spy, $pointcuts);
                }

                $dependencyString = $dependencyStrings[$dependencyString];
            }

            $log[] = sprintf('%s => %s', $dependencyIndex, $this->label($dependencyIndex, $dependencyString));
        }

        sort($log);

        return implode(PHP_EOL, $log);
    }

    /**
     * Collapse a class bound to itself to '(untargeted)', matching
     * {@see BindingEvent::label()} so the resolved bindings agree with the
     * provenance.
     */
    private function label(string $index, string $dependency): string
    {
        $prefix = '(dependency) ';
        if (str_starts_with($dependency, $prefix) && $index === substr($dependency, strlen($prefix)) . '-') {
            return '(untargeted)';
        }

        return $dependency;
    }
}
