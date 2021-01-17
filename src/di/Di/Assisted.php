<?php

declare(strict_types=1);

namespace Ray\Di\Di;

use Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;
use Ray\Aop\Annotation\AbstractAssisted;

/**
 * Annotates your class methods into which the Injector should pass the values on method invocation
 *
 * @Annotation
 * @Target("METHOD")
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Assisted extends AbstractAssisted implements NamedArgumentConstructorAnnotation
{
    /** @var array */
    public $values;

    public function __construct(array $value)
    {
        $this->values = $value;
    }
}
