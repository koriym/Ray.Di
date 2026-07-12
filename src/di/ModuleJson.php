<?php

declare(strict_types=1);

namespace Ray\Di;

use function gettype;
use function is_object;
use function is_scalar;
use function json_encode;
use function serialize;
use function unserialize;

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * @psalm-import-type PointcutList from Types
 * @psalm-type BindingType = 'class'|'provider'|'instance'|'null'
 * @psalm-type AopBindings = array<string, list<string>>
 * @psalm-type BindingEntry = array{interface: string, name: string, type: BindingType, to: mixed, aop?: AopBindings}
 * @psalm-type ModuleBindings = array{bindings: list<BindingEntry>}
 */
final class ModuleJson
{
    public function __construct(
        private BindingTargetVisitor $targetVisitor = new BindingTargetVisitor()
    ) {
    }

    /**
     * @param PointcutList $pointcuts
     */
    public function __invoke(Container $container, array $pointcuts): string
    {
        $bindings = $this->getBindings($container, $pointcuts);

        // JSON_INVALID_UTF8_SUBSTITUTE: one binary toInstance() value must not
        // erase the whole diagnostics report
        $json = json_encode($bindings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            return '{"bindings":[]}'; // @codeCoverageIgnore
        }

        return $json;
    }

    /**
     * @param PointcutList $pointcuts
     *
     * @return ModuleBindings
     */
    public function getBindings(Container $container, array $pointcuts): array
    {
        /** @psalm-suppress MixedAssignment */
        $container = unserialize(serialize($container), ['allowed_classes' => true]);
        /** @var Container $container */
        $collector = new AopCollector();
        $entries = [];

        foreach ($container->getContainer() as $dependencyIndex => $dependency) {
            $aopBindings = [];
            if ($dependency instanceof Dependency) {
                $aopBindings = $collector->collect($dependency, $pointcuts);
            }

            $entry = $this->createEntry($dependencyIndex, $dependency, $aopBindings);
            if ($entry !== null) {
                $entries[] = $entry;
            }
        }

        return ['bindings' => $entries];
    }

    /**
     * @param AopBindings $aopBindings
     *
     * @return BindingEntry|null
     */
    private function createEntry(string $dependencyIndex, DependencyInterface $dependency, array $aopBindings): ?array
    {
        [$interface, $name] = BindingIndex::parse($dependencyIndex);

        if ($dependency instanceof Dependency) {
            return $this->createDependencyEntry($interface, $name, $dependency, $aopBindings);
        }

        if ($dependency instanceof DependencyProvider) {
            return $this->createProviderEntry($interface, $name, $dependency);
        }

        if ($dependency instanceof Instance) {
            return $this->createInstanceEntry($interface, $name, $dependency);
        }

        if ($dependency instanceof NullObjectDependency) {
            return [
                'interface' => $interface,
                'name' => $name,
                'type' => 'null',
                'to' => null,
            ];
        }

        return null; // @codeCoverageIgnore
    }

    /**
     * @param AopBindings $aopBindings
     *
     * @return BindingEntry
     */
    private function createDependencyEntry(string $interface, string $name, Dependency $dependency, array $aopBindings): array
    {
        $entry = [
            'interface' => $interface,
            'name' => $name,
            'type' => 'class',
            'to' => $this->targetVisitor->targetClass($dependency),
        ];

        if ($aopBindings !== []) {
            $entry['aop'] = $aopBindings;
        }

        return $entry;
    }

    /**
     * @return BindingEntry
     */
    private function createProviderEntry(string $interface, string $name, DependencyProvider $dependency): array
    {
        return [
            'interface' => $interface,
            'name' => $name,
            'type' => 'provider',
            'to' => $this->targetVisitor->targetClass($dependency),
        ];
    }

    /**
     * @return BindingEntry
     */
    private function createInstanceEntry(string $interface, string $name, Instance $dependency): array
    {
        return [
            'interface' => $interface,
            'name' => $name,
            'type' => 'instance',
            'to' => $this->formatValue($dependency->accept($this->targetVisitor)),
        ];
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function formatValue($value)
    {
        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if (is_object($value)) {
            return ['__class' => $value::class];
        }

        return ['__type' => gettype($value)];
    }
}
