<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use LogicException;

/**
 * Message format: '{interface}-{name}' of the existing target binding
 *
 * Renaming over an existing binding would silently destroy it,
 * so the conflict is reported instead.
 */
final class RenameTargetAlreadyBound extends LogicException implements ExceptionInterface
{
}
