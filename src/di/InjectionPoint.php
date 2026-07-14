<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\ReflectionClass;
use Ray\Aop\ReflectionMethod;
use Ray\Di\Di\Qualifier;
use ReflectionClass as CoreReflectionClass;
use ReflectionParameter;

use function assert;
use function class_exists;

final class InjectionPoint implements InjectionPointInterface
{
    private string $pClass;

    /** @var string */
    private $pFunction;

    /** @var string */
    private $pName;

    public function __construct(private ReflectionParameter $parameter)
    {
        $this->pFunction = $this->parameter->getDeclaringFunction()->name;
        $class = $this->parameter->getDeclaringClass();
        $this->pClass = $class instanceof CoreReflectionClass ? $class->name : '';
        $this->pName = $this->parameter->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getParameter(): ReflectionParameter
    {
        return $this->parameter;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod(): ReflectionMethod
    {
        $class = $this->parameter->getDeclaringClass();
        $method = $this->parameter->getDeclaringFunction()->getShortName();
        assert($class instanceof CoreReflectionClass);
        assert(class_exists($class->getName()));

        return new ReflectionMethod($class->getName(), $method);
    }

    /**
     * {@inheritDoc}
     */
    public function getClass(): ReflectionClass
    {
        $class = $this->parameter->getDeclaringClass();
        assert($class instanceof CoreReflectionClass);

        return new ReflectionClass($class->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function getQualifiers(): array
    {
        $qualifiers = [];
        $annotations = $this->getMethod()->getAnnotations();
        foreach ($annotations as $annotation) {
            $maybeQualifier = (new ReflectionClass($annotation))->getAnnotation(Qualifier::class);
            if ($maybeQualifier instanceof Qualifier) {
                $qualifiers[] = $annotation;
            }
        }

        return $qualifiers;
    }

    /** @return array<string> */
    public function __serialize(): array
    {
        return [$this->pClass, $this->pFunction, $this->pName];
    }

    /**
     * Rebuild the ReflectionParameter dropped by serialization.
     *
     * The enclosing container is serialized (e.g. by ModuleString and
     * compiled-container caches), so a restored InjectionPoint must stay usable;
     * the typed $parameter would otherwise be left uninitialized.
     *
     * @param array<string> $array
     */
    public function __unserialize(array $array): void
    {
        [$this->pClass, $this->pFunction, $this->pName] = $array;
        if ($this->pClass !== '') {
            $this->parameter = new ReflectionParameter([$this->pClass, $this->pFunction], $this->pName);
        }
    }
}
