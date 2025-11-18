<?php

declare(strict_types=1);

namespace Ray\Di;

use function explode;
use function substr;
use function trigger_error;
use function trim;

use const E_USER_DEPRECATED;

/**
 * Legacy string format parser for toConstructor() method
 *
 * Parses "key=value,key=value" string format for backward compatibility.
 * This format is deprecated. Use parameter-level attributes or array format instead.
 *
 * @deprecated Use parameter-level attributes (#[Named('name')]) or array format (['param' => 'name'])
 *
 * @internal
 */
final class LegacyStringParser
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
        trigger_error(
            'String format in toConstructor() is deprecated. Use parameter-level attributes (#[Named(\'name\')]) or array format ([\'param\' => \'name\']) instead.',
            E_USER_DEPRECATED,
        );

        $names = [];
        $keyValues = explode(',', $name);
        foreach ($keyValues as $keyValue) {
            $exploded = explode('=', $keyValue);
            if (isset($exploded[1])) {
                [$key, $value] = $exploded;
                if (isset($key[0]) && $key[0] === '$') {
                    $key = substr($key, 1);
                }

                $trimedKey = trim($key);

                $names[$trimedKey] = trim($value);
            }
        }

        return $names;
    }
}
