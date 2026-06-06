<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\Named;
use Ray\Di\Di\Qualifier;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;

use function assert;
use function class_exists;
use function is_string;
use function preg_match;

/**
 * @psalm-import-type ParameterNameMapping from Types
 */
final class Name
{
    /**
     * 'Unnamed' name
     */
    public const ANY = '';

    private string $name = '';

    /**
     * Named database
     *
     * format: array<varName, NamedName>
     *
     * @var ParameterNameMapping
     */
    private array $names = [];

    /**
     * @param string|ParameterNameMapping $name
     */
    public function __construct(string|array $name)
    {
        if (is_string($name)) {
            $this->setName($name);

            return;
        }

        $this->names = $name;
    }

    /**
     * Create instance from PHP8 attributes
     *
     * psalm does not know ReflectionAttribute?? PHPStan produces no type error here.
     */
    public static function withAttributes(ReflectionMethod $method): ?self
    {
        $params = $method->getParameters();
        $names = [];
        foreach ($params as $param) {
            /** @var array<ReflectionAttribute> $attributes */
            $attributes = $param->getAttributes();
            if ($attributes) {
                $name = self::getName($attributes);
                $names[$param->name] = $name;
            }
        }

        // Backward compatibility: Get parameter qualifiers from method-level attribute
        if ($names === []) {
            /** @psalm-suppress DeprecatedClass */
            $names = BcParameterQualifier::getNames($method);
        }

        if ($names !== []) {
            return new self($names);
        }

        return null;
    }

    /**
     * @param non-empty-array<ReflectionAttribute> $attributes
     *
     * @throws ReflectionException
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     */
    private static function getName(array $attributes): string
    {
        $refAttribute = $attributes[0];
        /** @var class-string $attributeName */
        $attributeName = $refAttribute->getName();
        if ($attributeName === Named::class) {
            $attribute = $refAttribute->newInstance();
            assert($attribute instanceof Named);

            return $attribute->value;
        }

        $isQualifier = (bool) (new ReflectionClass($attributeName))->getAttributes(Qualifier::class);
        if ($isQualifier) {
            return $attributeName;
        }

        return '';
    }

    public function __invoke(ReflectionParameter $parameter): string
    {
        // single variable named binding
        if ($this->name) {
            return $this->name;
        }

        $parameterName = $parameter->name;

        // multiple variable named binding
        return $this->names[$parameterName] ?? $this->names[self::ANY] ?? self::ANY;
    }

    private function setName(string $name): void
    {
        // annotation
        if (class_exists($name, false)) {
            $this->name = $name;

            return;
        }

        // single name
        // @Named(name)
        if ($name === self::ANY || preg_match('/^\w+$/', $name)) {
            $this->name = $name;

            return;
        }

        // name list (backward compatibility)
        // @Named(varName1=name1, varName2=name2) or toConstructor string format
        /** @psalm-suppress DeprecatedClass */
        $this->names = BcStringParser::parse($name);
    }
}
