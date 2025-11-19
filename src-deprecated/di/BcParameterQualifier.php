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
 * Backward compatible parameter qualifier for single-parameter setters
 *
 * Automatically applies method-level InjectInterface+Qualifier attributes to parameters
 * when the method has only one parameter and no parameter-level qualifier is specified.
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
     * 3. Method has an attribute implementing both InjectInterface and Qualifier
     * 4. The attribute supports TARGET_PARAMETER (not just TARGET_METHOD)
     *
     * @param ReflectionMethod $method The setter method to analyze
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
     * 3. Method has an attribute implementing both InjectInterface and Qualifier
     * 4. The attribute supports TARGET_PARAMETER (not just TARGET_METHOD)
     *
     * @param ReflectionMethod $method The setter method to analyze
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

        // Check method-level attributes for InjectInterface+Qualifier combination
        $methodAttributes = $method->getAttributes();
        foreach ($methodAttributes as $attr) {
            $instance = $attr->newInstance();

            // Must implement InjectInterface
            if (! $instance instanceof InjectInterface) {
                continue;
            }

            // Must also be marked with Qualifier
            $attrClass = new ReflectionClass($attr->getName());
            $qualifierAttr = $attrClass->getAttributes(Qualifier::class);

            if ($qualifierAttr === []) {
                continue;
            }

            // IMPORTANT: Only infer if attribute supports TARGET_PARAMETER
            // If it's TARGET_METHOD only, it's meant for Provider/InjectionPoint pattern
            if (! self::supportsParameterTarget($attrClass)) {
                continue;
            }

            // Found a method-level attribute that is both Inject and Qualifier
            // AND supports being used at parameter level
            return $attr->getName();
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

    /**
     * Check if attribute supports TARGET_PARAMETER
     *
     * @param ReflectionClass<object> $attrClass
     */
    private static function supportsParameterTarget(ReflectionClass $attrClass): bool
    {
        $attributeAttrs = $attrClass->getAttributes(\Attribute::class);
        if ($attributeAttrs === []) {
            return false;
        }

        $attributeInstance = $attributeAttrs[0]->newInstance();
        if (! $attributeInstance instanceof \Attribute) {
            return false;
        }

        // Check if Attribute::TARGET_PARAMETER is included in the flags
        return ($attributeInstance->flags & \Attribute::TARGET_PARAMETER) !== 0;
    }
}
