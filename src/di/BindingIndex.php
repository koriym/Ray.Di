<?php

declare(strict_types=1);

namespace Ray\Di;

use function explode;

/**
 * Single source of truth for the '{interface}-{name}' dependency index format
 *
 * Container keys are always built as interface . '-' . name.
 */
final class BindingIndex
{
    /**
     * Split a dependency index into [interface, bind name]
     *
     * Splits on the FIRST hyphen only: PHP class names cannot contain
     * hyphens, but bind names can (e.g. 'type-bool'), so everything after
     * the first hyphen belongs to the name.
     *
     * @return array{string, string}
     */
    public static function parse(string $index): array
    {
        /** @psalm-suppress PossiblyUndefinedArrayOffset -- $index is always "{interface}-{name}" */
        [$interface, $name] = explode('-', $index, 2);

        return [$interface, $name];
    }
}
