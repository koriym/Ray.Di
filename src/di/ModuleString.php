<?php

declare(strict_types=1);

namespace Ray\Di;

use function assert;
use function implode;
use function serialize;
use function sort;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;
use function unserialize;

use const PHP_EOL;

/** @psalm-import-type PointcutList from Types */
final class ModuleString
{
    /** @param PointcutList $pointcuts */
    public function __invoke(Container $container, array $pointcuts): string
    {
        $log = [];
        /** @psalm-suppress MixedAssignment */
        $container = unserialize(serialize($container), ['allowed_classes' => true]);
        assert($container instanceof Container);
        $spy = new SpyCompiler();
        foreach ($container->getContainer() as $dependencyIndex => $dependency) {
            if ($dependency instanceof Dependency) {
                $dependency->weaveAspects($spy, $pointcuts);
            }

            $log[] = sprintf('%s => %s', $dependencyIndex, $this->label($dependencyIndex, (string) $dependency));
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
