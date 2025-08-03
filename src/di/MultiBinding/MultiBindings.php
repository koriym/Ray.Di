<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use ArrayObject;
use Ray\Di\Types;

use function array_merge_recursive;

/**
 * @psalm-import-type LazyBindingList from Types
 * @extends ArrayObject<string, LazyBindingList>
 */
final class MultiBindings extends ArrayObject
{
    public function merge(self $multiBindings): void
    {
        $this->exchangeArray(
            array_merge_recursive($this->getArrayCopy(), $multiBindings->getArrayCopy())
        );
    }
}
