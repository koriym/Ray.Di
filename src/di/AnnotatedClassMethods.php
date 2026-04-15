<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\ReflectionClass;
use Ray\Aop\ReflectionMethod;
use Ray\Di\Di\InjectInterface;
use ReflectionAttribute;

final class AnnotatedClassMethods
{
    /**
     * @phpstan-param ReflectionClass<object> $class
     */
    public function getConstructorName(ReflectionClass $class): Name
    {
        $constructor = $class->getConstructor();
        if (! $constructor instanceof \ReflectionMethod) {
            return new Name(Name::ANY);
        }

        $reflMethod = new \ReflectionMethod($class->getName(), '__construct');
        $name = Name::withAttributes($reflMethod);
        if ($name instanceof Name) {
            return $name;
        }

        return LegacyAttributeHelper::getNameFromMethod($reflMethod) ?? new Name(Name::ANY);
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
        $name = Name::withAttributes($method);
        if ($name instanceof Name) {
            return $name;
        }

        return LegacyAttributeHelper::getNameFromMethod($method) ?? new Name(Name::ANY);
    }
}
