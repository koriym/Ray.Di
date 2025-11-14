<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\Named;

class FakeInternalTypes
{
    public $bool;
    public $int;
    public $string;
    public $array;
    public $callable;

    public function __construct(
        #[Named('type-bool')] bool $bool,
        #[Named('type-int')] int $int,
        #[Named('type-string')] string $string,
        #[Named('type-array')] array $array,
        #[Named('type-callable')] callable $callable
    ) {
        $this->bool = $bool;
        $this->int = $int;
        $this->string = $string;
        $this->array = $array;
        $this->callable = $callable;
    }

    public function stringId(string $id): void
    {
        unset($id);
    }
}
