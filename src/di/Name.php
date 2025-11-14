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

use function class_exists;
use function get_class;
use function is_string;

/**
 * @psalm-import-type ParameterNameMapping from Types
 */
final class Name
{
    /**
     * 'Unnamed' name
     */
    public const ANY = '';

    /** @var string */
    private $name = '';

    /**
     * Named database
     *
     * format: array<varName, NamedName>
     *
     * @var ParameterNameMapping
     */
    private $names;

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

        if ($names) {
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
        $attribute = $refAttribute->newInstance();
        if ($attribute instanceof Named) {
            return $attribute->value;
        }

        $isQualifier = (bool) (new ReflectionClass($attribute))->getAttributes(Qualifier::class);
        if ($isQualifier) {
            return get_class($attribute);
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
        $this->name = $name;
    }
}
