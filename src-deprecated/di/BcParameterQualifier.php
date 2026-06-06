<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\InjectInterface;
use Ray\Di\Di\Qualifier;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

use function count;

/**
 * Backward compatible parameter qualifier for single-parameter methods
 *
 * Automatically applies method-level Qualifier attributes to parameters when:
 * - Method has exactly one parameter
 * - Parameter has no explicit qualifier
 * - For constructors: Method has a Qualifier attribute (InjectInterface is implicit)
 * - For setters: Method has an attribute implementing both InjectInterface and Qualifier
 *
 * This behavior is deprecated for the following reasons:
 * - Violates Single Responsibility Principle (one attribute serving dual purposes)
 * - Creates fragility when refactoring (adding parameters changes behavior)
 * - Reduces code clarity (implicit rather than explicit)
 *
 * @deprecated Use explicit separation: #[Inject] at method level, Qualifier at parameter level
 *
 * Recommended migration:
 *   OLD (implicit):
 *     #[FakeLogDbInject]
 *     public function setDb(ExtendedPdoInterface $pdo) { }
 *
 *   NEW (explicit):
 *     #[Inject]
 *     public function setDb(#[FakeLogDb] ExtendedPdoInterface $pdo) { }
 *
 * @internal
 */
final class BcParameterQualifier
{
    /**
     * Get parameter qualifier names from method-level attribute if applicable
     *
     * Returns parameter name mapping if:
     * 1. Method has exactly one parameter
     * 2. Parameter has no qualifier attribute
     * 3. For setters: Method has an attribute implementing both InjectInterface and Qualifier
     * 4. For constructors: Method has a Qualifier attribute (InjectInterface is implicit)
     *
     * @param ReflectionMethod $method The method to analyze
     *
     * @return array<string, string> Parameter name to qualifier mapping (empty if not applicable)
     */
    public static function getNames(ReflectionMethod $method): array
    {
        $params = $method->getParameters();

        // Only for single-parameter methods
        if (count($params) !== 1) {
            return [];
        }

        $qualifier = self::getQualifier($method);
        if ($qualifier === '') {
            return [];
        }

        return [$params[0]->name => $qualifier];
    }

    /**
     * Get parameter qualifier from method-level attribute if applicable
     *
     * Returns the qualifier name if:
     * 1. Method has exactly one parameter
     * 2. Parameter has no qualifier attribute
     * 3. For setters: Method has an attribute implementing both InjectInterface and Qualifier
     * 4. For constructors: Method has a Qualifier attribute (InjectInterface is implicit)
     *
     * @param ReflectionMethod $method The method to analyze
     *
     * @return string The qualifier class name, or empty string if not applicable
     */
    private static function getQualifier(ReflectionMethod $method): string
    {
        $params = $method->getParameters();

        // Only for single-parameter methods
        if (count($params) !== 1) {
            return '';
        }

        $param = $params[0];

        // Check if parameter already has a qualifier
        if (self::hasParameterQualifier($param->getAttributes())) {
            return '';
        }

        $isConstructor = $method->name === '__construct';

        // Check method-level attributes for Qualifier
        $methodAttributes = $method->getAttributes();
        foreach ($methodAttributes as $attr) {
            $attributeName = $attr->getName();
            $attrClass = new ReflectionClass($attributeName);
            $qualifierAttr = $attrClass->getAttributes(Qualifier::class);

            // Skip if not a Qualifier
            if ($qualifierAttr === []) {
                continue;
            }

            // For constructors: Qualifier alone is sufficient (InjectInterface is implicit)
            if ($isConstructor) {
                return $attributeName;
            }

            // For setters: Must also implement InjectInterface
            if ($attrClass->implementsInterface(InjectInterface::class)) {
                return $attributeName;
            }
        }

        return '';
    }

    /**
     * Check if parameter already has a qualifier attribute
     *
     * @param array<ReflectionAttribute> $attributes
     */
    private static function hasParameterQualifier(array $attributes): bool
    {
        foreach ($attributes as $attr) {
            $attrClass = new ReflectionClass($attr->getName());

            // Check for Qualifier marker
            if ($attrClass->getAttributes(Qualifier::class) !== []) {
                return true;
            }
        }

        return false;
    }
}
