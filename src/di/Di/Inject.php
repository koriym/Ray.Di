<?php

declare(strict_types=1);

namespace Ray\Di\Di;

use Attribute;

/**
 * Annotates your class methods into which the Injector should inject values
 *
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER)]
final class Inject implements InjectInterface
{
    /** @SuppressWarnings("PHPMD.BooleanArgumentFlag") */
    public function __construct(
        /**
         * If true, and the appropriate binding is not found, the Injector will skip injection of this method or field rather than produce an error.
         */
        public bool $optional = false
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function isOptional(): bool
    {
        return $this->optional;
    }
}
