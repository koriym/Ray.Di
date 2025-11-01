<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\ReflectionClass;
use Ray\Aop\ReflectionMethod;
use Ray\Di\Di\InjectInterface;
use Ray\Di\Di\Named;
use Ray\Di\Di\Qualifier;
use ReflectionAttribute;

use function explode;
use function get_class;
use function property_exists;
use function str_contains;
use function substr;
use function trim;

final class AnnotatedClassMethods
{
    /**
     * @phpstan-param ReflectionClass<object> $class
     */
    public function getConstructorName(ReflectionClass $class): Name
    {
        $constructor = $class->getConstructor();
        if (! $constructor) {
            return new Name(Name::ANY);
        }

        $reflMethod = new \ReflectionMethod($class->getName(), '__construct');

        // First check for parameter-level attributes
        $name = Name::withAttributes($reflMethod);
        if ($name) {
            return $name;
        }

        // Check for method-level Named and Qualifier attributes
        $names = $this->getMethodLevelQualifiers(new ReflectionMethod($class->getName(), '__construct'));
        if ($names !== []) {
            return new Name($names);
        }

        return new Name(Name::ANY);
    }

    public function getSetterMethod(ReflectionMethod $method): ?SetterMethod
    {
        $inject = $method->getAnnotation(InjectInterface::class, ReflectionAttribute::IS_INSTANCEOF);
        if (! $inject instanceof InjectInterface) {
            return null;
        }

        $name = $this->getName($method);
        $setterMethod = new SetterMethod($method, $name);
        if ($inject->isOptional()) {
            $setterMethod->setOptional();
        }

        return $setterMethod;
    }

    private function getName(ReflectionMethod $method): Name
    {
        // First check for parameter-level attributes
        $name = Name::withAttributes($method);
        if ($name) {
            return $name;
        }

        // Check for method-level Named and Qualifier attributes
        $names = $this->getMethodLevelQualifiers($method);
        if ($names !== []) {
            return new Name($names);
        }

        return new Name(Name::ANY);
    }

    /**
     * Extract qualifier information from method-level attributes
     *
     * @return array<string, string>
     */
    private function getMethodLevelQualifiers(ReflectionMethod $method): array
    {
        $names = [];
        $attributes = $method->getAttributes();

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();

            // Check if this attribute is a qualifier
            $refClass = new ReflectionClass($instance);
            $qualifierAttrs = $refClass->getAttributes(Qualifier::class);

            if ($qualifierAttrs !== []) {
                $qualifierClass = get_class($instance);

                // Get the parameter name from the qualifier's value property if it exists
                if (property_exists($instance, 'value') && $instance->value !== null) {
                    $names[(string) $instance->value] = $qualifierClass;
                } else {
                    // If no value, apply to all parameters
                    $names[Name::ANY] = $qualifierClass;
                }
            } elseif ($instance instanceof Named) {
                // Handle @Named at method level - parse as var1=name1,var2=name2
                $namedValue = $instance->value;
                if (str_contains($namedValue, '=')) {
                    // Multiple variable mapping: var1=name1,var2=name2
                    $keyValues = explode(',', $namedValue);
                    foreach ($keyValues as $keyValue) {
                        $exploded = explode('=', $keyValue);
                        if (isset($exploded[1])) {
                            [$key, $value] = $exploded;
                            if (isset($key[0]) && $key[0] === '$') {
                                $key = substr($key, 1);
                            }

                            $names[trim($key)] = trim($value);
                        }
                    }
                } else {
                    // Single name for all parameters
                    $names[Name::ANY] = $namedValue;
                }
            }
        }

        return $names;
    }
}
