<?php

declare(strict_types=1);

namespace Ray\Di\Annotation;

use Attribute;
use Ray\Di\Di\Qualifier;

/**
 * Qualifier-only attribute for testing (no InjectInterface)
 */
#[Attribute(Attribute::TARGET_METHOD), Qualifier]
final class FakeQualifierOnly
{
}
