<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

/**
 * Exception thrown when a parameter has no type hint and no binding name
 *
 * Message format: ${var} (file:line)
 *
 * This occurs when Ray.Di cannot identify what to inject because
 * the parameter has neither a class/interface type hint nor a #[Named] or #[Qualifier] attribute.
 */
final class NoHint extends Unbound
{
}
