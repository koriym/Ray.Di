<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use ArrayObject;
use Ray\Di\Types;

use function is_int;

/**
 * @psalm-import-type LazyBindingList from Types
 * @extends ArrayObject<string, LazyBindingList>
 */
final class MultiBindings extends ArrayObject
{
    /**
     * An existing string key keeps its first entry, matching the += winner of
     * the container merge; integer-keyed (unnamed) entries append
     */
    public function merge(self $multiBindings): void
    {
        foreach ($multiBindings->getArrayCopy() as $interface => $lazies) {
            if (! $this->offsetExists($interface)) {
                $this->offsetSet($interface, $lazies);
                continue;
            }

            /** @var LazyBindingList $existing */
            $existing = $this[$interface];
            foreach ($lazies as $key => $lazy) {
                if (is_int($key)) {
                    $existing[] = $lazy;
                    continue;
                }

                if (! isset($existing[$key])) {
                    $existing[$key] = $lazy;
                }
            }

            $this->offsetSet($interface, $existing);
        }
    }
}
