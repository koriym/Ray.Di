<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Di\Annotation\FakeInjectOne;

class FakeClassWithLegacyInference
{
    public $gearStick;

    /**
     * Method-level #[FakeInjectOne] without parameter-level qualifier
     * Legacy inference should apply FakeInjectOne to the parameter
     */
    #[FakeInjectOne]
    public function setGearStick(FakeGearStickInterface $gearStick): void
    {
        $this->gearStick = $gearStick;
    }
}
