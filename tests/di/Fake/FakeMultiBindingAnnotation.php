<?php

declare(strict_types=1);

namespace Ray\Di;


use Ray\Di\Di\Set;
use Ray\Di\MultiBinding\Map;

final class FakeMultiBindingAnnotation
{
    /** @var Map<FakeEngineInterface> */
    public $engines;

    /** @var Map<FakeRobotInterface> */
    public $robots;

    public function __construct(
        #[Set(FakeEngineInterface::class)]
        Map $engines,
        #[Set(FakeRobotInterface::class)]
        Map $robots
    ){
        $this->engines = $engines;
        $this->robots = $robots;
    }
}
