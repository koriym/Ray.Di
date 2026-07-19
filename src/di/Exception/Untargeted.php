<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

/**
 * Thrown when an unbound but instantiable concrete class is requested
 *
 * Message format: {class name}
 *
 * Runtime just-in-time binding is no longer performed, so the Injector lets
 * this exception propagate to the caller.
 */
final class Untargeted extends Unbound
{
}
