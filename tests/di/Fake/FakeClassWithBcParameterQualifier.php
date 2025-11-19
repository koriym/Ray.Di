<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Annotation\FakeInjectOne;

class FakeClassWithBcParameterQualifier
{
    public $gearStick;

    /**
     * Method-level #[FakeInjectOne] without parameter-level qualifier
     * BcParameterQualifier should apply FakeInjectOne to the parameter
     */
    #[FakeInjectOne]
    public function setGearStick(FakeGearStickInterface $gearStick): void
    {
        $this->gearStick = $gearStick;
    }
}
