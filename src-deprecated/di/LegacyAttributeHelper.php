<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\Named;
use Ray\Di\Di\Qualifier;
use Ray\Di\Exception\InvalidToConstructorNameParameter;
use ReflectionClass;
use ReflectionMethod;

use function array_keys;
use function array_reduce;
use function explode;
use function get_class;
use function implode;
use function is_string;
use function property_exists;
use function str_contains;
use function substr;
use function trigger_error;
use function trim;

use const E_USER_DEPRECATED;

/**
 * Helper class for supporting legacy method-level attributes
 *
 * This class provides backward compatibility support for deprecated method-level
 * attribute syntax. It is not deprecated itself, but supports deprecated features.
 *
 * @internal This class is used internally by Ray.Di to support legacy code.
 */
final class LegacyAttributeHelper
{
    /**
     * Get Name from method-level attributes (high-level wrapper)
     *
     * Supports deprecated method-level qualifier attributes for backward compatibility.
     */
    public static function getNameFromMethod(ReflectionMethod $method): ?Name
    {
        $names = self::getMethodLevelQualifiers($method);
        if ($names === []) {
            return null;
        }

        return new Name($names);
    }

    /**
     * Parse legacy string format with deprecation warning (high-level wrapper)
     *
     * Supports deprecated string-based name mapping for backward compatibility.
     *
     * @return array<string, string>
     */
    public static function parseNameWithWarning(string $name): array
    {
        trigger_error(
            'String-based parameter name mapping like "var1=name1,var2=name2" is deprecated. ' .
            'Use parameter-level #[Named] attributes or array format instead.',
            E_USER_DEPRECATED
        );

        return self::parseName($name);
    }

    /**
     * Convert array to string with deprecation warning (high-level wrapper)
     *
     * Supports deprecated array parameter format for backward compatibility.
     *
     * @param array<string, string> $name
     */
    public static function convertArrayToStringWithWarning(array $name): string
    {
        trigger_error(
            'Array parameter to toConstructor() is deprecated. Use Name class constructor with array directly.',
            E_USER_DEPRECATED
        );

        return self::getStringName($name);
    }

    /**
     * Extract qualifier information from method-level attributes
     *
     * Low-level implementation for parsing deprecated method-level qualifiers.
     *
     * @return array<string, string>
     */
    public static function getMethodLevelQualifiers(ReflectionMethod $method): array
    {
        $names = [];
        $attributes = $method->getAttributes();

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();

            // Check if this attribute is a qualifier
            $refClass = new ReflectionClass($instance);
            $qualifierAttrs = $refClass->getAttributes(Qualifier::class);

            if ($qualifierAttrs !== []) {
                trigger_error(
                    'Method-level qualifier attributes are deprecated. Use parameter-level attributes instead. ' .
                    'Found on ' . $method->class . '::' . $method->name . '()',
                    E_USER_DEPRECATED
                );

                $qualifierClass = get_class($instance);

                // Get the parameter name from the qualifier's value property if it exists
                if (property_exists($instance, 'value') && $instance->value !== null) {
                    $names[(string) $instance->value] = $qualifierClass;

                    continue;
                }

                // If no value, apply to all parameters
                $names[Name::ANY] = $qualifierClass;

                continue;
            }

            if (! ($instance instanceof Named)) {
                continue;
            }

            trigger_error(
                'Method-level #[Named] attribute is deprecated. Use parameter-level #[Named] instead. ' .
                'Found on ' . $method->class . '::' . $method->name . '()',
                E_USER_DEPRECATED
            );

            // Handle @Named at method level - parse as var1=name1,var2=name2
            $namedValue = $instance->value;
            if (str_contains($namedValue, '=')) {
                // Multiple variable mapping: var1=name1,var2=name2
                $keyValues = explode(',', $namedValue);
                foreach ($keyValues as $keyValue) {
                    $exploded = explode('=', $keyValue);
                    if (! isset($exploded[1])) {
                        continue;
                    }

                    [$key, $value] = $exploded;
                    if (isset($key[0]) && $key[0] === '$') {
                        $key = substr($key, 1);
                    }

                    $names[trim($key)] = trim($value);
                }

                continue;
            }

            // Single name for all parameters
            $names[Name::ANY] = $namedValue;
        }

        return $names;
    }

    /**
     * Parse legacy string-based name mapping
     *
     * Low-level implementation for parsing deprecated string format.
     *
     * @return array<string, string>
     *
     * @psalm-pure
     */
    public static function parseName(string $name): array
    {
        $names = [];
        $keyValues = explode(',', $name);
        foreach ($keyValues as $keyValue) {
            $exploded = explode('=', $keyValue);
            if (! isset($exploded[1])) {
                continue;
            }

            [$key, $value] = $exploded;
            if (isset($key[0]) && $key[0] === '$') {
                $key = substr($key, 1);
            }

            $trimedKey = trim($key);

            $names[$trimedKey] = trim($value);
        }

        return $names;
    }

    /**
     * Convert array to string format for backward compatibility
     *
     * Low-level implementation for converting array to deprecated string format.
     *
     * input: ['varA' => 'nameA', 'varB' => 'nameB']
     * output: "varA=nameA,varB=nameB"
     *
     * @param array<string, string> $name
     */
    public static function getStringName(array $name): string
    {
        $keys = array_keys($name);

        $names = array_reduce(
            $keys,
            /**
             * @param list<string> $carry
             * @param array-key $key
             */
            static function (array $carry, $key) use ($name): array {
                if (! is_string($key)) {
                    throw new InvalidToConstructorNameParameter((string) $key);
                }

                $varName = $name[$key] ?? '';
                $carry[] = $key . '=' . $varName;

                return $carry;
            },
            []
        );

        return implode(',', $names);
    }
}
