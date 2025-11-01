<?php

declare(strict_types=1);

namespace Ray\Di;

#[FakeConstant('class_constant_val')]
class FakeWalkRobot
{
    /** @var FakeLegInterface */
    public $leftLeg;

    /** @var FakeLegInterface */
    public $rightLeg;

    #[FakeConstant(10)]
    #[FakeAnnoMethod1]
    public function __construct(FakeLegInterface $rightLeg, FakeLegInterface $leftLeg)
    {
        $this->rightLeg = $rightLeg;
        $this->leftLeg = $leftLeg;
    }
}
