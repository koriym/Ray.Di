<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use LogicException;

/**
 * Thrown when a #[Set] injection point finds no set for its interface
 *
 * Message format: '{interface}' in file:line ($var)
 *
 * MultiBinder::newInstance() declares the set, so an interface declared with
 * zero members injects an empty Map rather than reaching here. Reaching here
 * means the interface is absent from the MultiBindings this injection point
 * received: no MultiBinder ran for it, a setBinding() dropped it and the chain
 * stopped before to() put it back, or a sibling module's declarations never
 * arrived -- each MultiBinder binds MultiBindings to its own instance.
 */
final class MultiBindingNotFound extends LogicException implements ExceptionInterface
{
}
