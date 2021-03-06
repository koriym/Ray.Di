<?php

declare(strict_types=1);

namespace Ray\Di;

use stdClass;

use function fopen;

class FakeInternalTypeModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind('')->annotatedWith('type-bool')->toInstance(false);
        $this->bind('')->annotatedWith('type-int')->toInstance(1);
        $this->bind('')->annotatedWith('type-string')->toInstance('1');
        $this->bind('')->annotatedWith('type-array')->toInstance([1]);
        $this->bind('')->annotatedWith('type-callable')->toInstance(static function () {
        });
        $this->bind('')->annotatedWith('type-object')->toInstance(new stdClass());
        $this->bind('')->annotatedWith('type-resource')->toInstance(fopen('data://,', 'w'));
    }
}
