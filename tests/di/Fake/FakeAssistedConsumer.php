<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Di\Assisted;
use Ray\Di\Di\Named;

class FakeAssistedConsumer
{
    /**
     * @return FakeRobotInterface|null
     */
    public function assistOne($a, $b, #[Assisted] ?FakeRobotInterface $robot = null)
    {
        unset($a, $b);

        return $robot;
    }

    public function assistWithName($a, #[Assisted] #[Named('one')] $var1 = null)
    {
        unset($a);

        return $var1;
    }

    /**
     * @return (FakeRobotInterface|mixed|null)[]
     * @psalm-return array{0: mixed, 1: FakeRobotInterface|null}
     */
    public function assistAny(#[Assisted] #[Named('one')] $var2 = null, #[Assisted] ?FakeRobotInterface $robot = null)
    {
        return [$var2, $robot];
    }
}
