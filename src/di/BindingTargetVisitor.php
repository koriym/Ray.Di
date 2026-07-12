<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\Bind as AopBind;
use ReflectionParameter;

/**
 * Collects what a binding resolves to, as structured data
 *
 * Visiting yields the bound class for a Dependency, the provider class and
 * its context for a DependencyProvider, and the raw value for an Instance,
 * so that callers do not have to parse __toString() output.
 */
final class BindingTargetVisitor implements VisitorInterface
{
    /** Captured by visitNewInstance(): the class the visited binding instantiates */
    private string $class = '';

    /**
     * Return the class a binding instantiates
     *
     * The bound class for a Dependency, the provider class for a
     * DependencyProvider. Offered as a typed entry point because accept()
     * returns mixed, which every caller would otherwise have to re-assert.
     */
    public function targetClass(Dependency|DependencyProvider $dependency): string
    {
        $dependency->accept($this);

        return $this->class;
    }

    /**
     * {@inheritDoc}
     *
     * @return string The class the dependency instantiates
     */
    public function visitDependency(NewInstance $newInstance, ?string $postConstruct, bool $isSingleton): string
    {
        $newInstance->accept($this);

        return $this->class;
    }

    /**
     * {@inheritDoc}
     *
     * @return array{class: string, context: string} Provider class and binding context
     */
    public function visitProvider(Dependency $dependency, string $context, bool $isSingleton): array
    {
        $dependency->accept($this);

        return ['class' => $this->class, 'context' => $context];
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $value
     *
     * @return mixed The raw bound value
     */
    public function visitInstance($value)
    {
        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * Aspect bindings carry no target information.
     */
    public function visitAspectBind(AopBind $aopBind)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     *
     * NewInstance::accept() discards the return value, so the class name is
     * captured in a property for visitDependency()/visitProvider() to read.
     */
    public function visitNewInstance(
        string $class,
        SetterMethods $setterMethods,
        ?Arguments $arguments,
        ?AspectBind $bind
    ): void {
        $this->class = $class;
    }

    /**
     * {@inheritDoc}
     *
     * Setter methods carry no target information.
     */
    public function visitSetterMethods(array $setterMethods)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     *
     * Setter methods carry no target information.
     */
    public function visitSetterMethod(string $method, Arguments $arguments)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     *
     * Arguments carry no target information.
     */
    public function visitArguments(array $arguments)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     *
     * Arguments carry no target information.
     *
     * @param mixed $defaultValue
     */
    public function visitArgument(
        string $index,
        bool $isDefaultAvailable,
        $defaultValue,
        ReflectionParameter $parameter
    ) {
        return null;
    }
}
