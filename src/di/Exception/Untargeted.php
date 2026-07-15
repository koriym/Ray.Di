<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

/**
 * Thrown when an unbound but instantiable concrete class is requested
 *
 * Message format: {class name}
 *
 * The Injector catches this exception to register the class as a
 * just-in-time binding and retry, so it normally never surfaces to callers.
 */
final class Untargeted extends Unbound
{
}
