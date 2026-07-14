<?php

declare(strict_types=1);

namespace Ray\Di;

use Exception;

/** @psalm-import-type SetterMethodsList from Types */
final class SetterMethods implements AcceptInterface
{
    /** @param SetterMethodsList $setterMethods */
    public function __construct(private array $setterMethods)
    {
    }

    /** @throws Exception */
    public function __invoke(object $instance, Container $container): void
    {
        foreach ($this->setterMethods as $setterMethod) {
            ($setterMethod)($instance, $container);
        }
    }

    public function add(?SetterMethod $setterMethod = null): void
    {
        if (! $setterMethod instanceof SetterMethod) {
            return;
        }

        $this->setterMethods[] = $setterMethod;
    }

    /** @inheritDoc */
    public function accept(VisitorInterface $visitor): void
    {
        $visitor->visitSetterMethods($this->setterMethods);
    }
}
