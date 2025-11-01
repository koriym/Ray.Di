<?php

declare(strict_types=1);

namespace Ray\Di\Di;

use Attribute;
use Ray\Aop\Annotation\AbstractAssisted;

/**
 * Annotates your class methods into which the Injector should pass the values on method invocation
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER)]
final class Assisted extends AbstractAssisted
{
    /** @var array<string> */
    public $values;

    /**
     * @param array<string> $value
     */
    public function __construct($value = [])
    {
        $this->values = $value;
    }
}
