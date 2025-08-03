<?php

declare(strict_types=1);

namespace Ray\Di;

use Exception;

/**
 * @psalm-import-type SetterMethodsList from Types
 */
final class SetterMethods implements AcceptInterface
{
    /** @var SetterMethodsList */
    private $setterMethods;

    /**
     * @param SetterMethodsList $setterMethods
     */
    public function __construct(array $setterMethods)
    {
        $this->setterMethods = $setterMethods;
    }

    /**
     * @throws Exception
     */
    public function __invoke(object $instance, Container $container): void
    {
        foreach ($this->setterMethods as $setterMethod) {
            ($setterMethod)($instance, $container);
        }
    }

    public function add(?SetterMethod $setterMethod = null): void
    {
        if (! $setterMethod) {
            return;
        }

        $this->setterMethods[] = $setterMethod;
    }

    /** @inheritDoc */
    public function accept(VisitorInterface $visitor)
    {
        $visitor->visitSetterMethods($this->setterMethods);
    }
}
