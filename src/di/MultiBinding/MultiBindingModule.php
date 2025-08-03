<?php

declare(strict_types=1);

namespace Ray\Di\MultiBinding;

use Koriym\ParamReader\ParamReader;
use Koriym\ParamReader\ParamReaderInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use Ray\Di\Types;

/**
 * @psalm-import-type BindableInterface from Types
 */
final class MultiBindingModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(MultiBindings::class);
        $this->bind(ParamReaderInterface::class)->to(ParamReader::class)->in(Scope::SINGLETON);
        $this->bind(Map::class)->toProvider(MapProvider::class);
    }
}
