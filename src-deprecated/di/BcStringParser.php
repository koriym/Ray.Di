<?php

declare(strict_types=1);

namespace Ray\Di;

use function explode;
use function substr;
use function trim;

/**
 * Backward compatible string format parser for toConstructor() method
 *
 * Parses "key=value,key=value" string format for backward compatibility.
 * This format is deprecated. Use parameter-level attributes or array format instead.
 *
 * @deprecated Use parameter-level attributes (#[Named('name')]) or array format (['param' => 'name'])
 *
 * @internal
 */
final class BcStringParser
{
    /**
     * Parse "key=value,key=value" format
     *
     * @return array<string, string>
     *
     * @psalm-pure
     */
    public static function parse(string $name): array
    {
        $names = [];
        $keyValues = explode(',', $name);
        foreach ($keyValues as $keyValue) {
            $exploded = explode('=', $keyValue, 2);
            if (isset($exploded[1])) {
                [$key, $value] = $exploded;
                if (isset($key[0]) && $key[0] === '$') {
                    $key = substr($key, 1);
                }

                $trimmedKey = trim($key);
                if ($trimmedKey === '') {
                    continue;
                }

                $names[$trimmedKey] = trim($value);
            }
        }

        return $names;
    }
}
