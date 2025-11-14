<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\Assisted;

class FakeAssistedParamsConsumer
{
    /**
     * @return array [int, FakeAbstractDb]
     */
    public function getUser($id, #[Assisted] ?FakeAbstractDb $db = null)
    {
        return [$id, $db];
    }
}
