<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\Compiler;

use function array_shift;

/**
 * @psalm-import-type ModuleList from Types
 * @psalm-import-type ScriptDir from Types
 */
final class ContainerFactory
{
    /**
     * @param AbstractModule|ModuleList|null $module   Module(s)
     * @param ScriptDir                      $classDir
     */
    public function __invoke($module, string $classDir): Container
    {
        $oneModule = $this->getModule($module);
        // install built-in module
        $appModule = (new BuiltinModule())($oneModule);
        $container = $appModule->getContainer();
        // Compile null objects
        (new CompileNullObject())($container, $classDir);
        // Emit bindings.md before weaving, while class names are the originals
        (new BindingsMarkdown())($container, $classDir);
        // Compile aspects
        /** @psalm-suppress InvalidArgument */
        $container->weaveAspects(new Compiler($classDir));

        return $container;
    }

    /** @param AbstractModule|ModuleList|null $module Module(s) */
    private function getModule($module): AbstractModule
    {
        if ($module instanceof AbstractModule) {
            return $module;
        }

        if ($module === null) {
            return new NullModule();
        }

        $modules = $module;
        $oneModule = array_shift($modules);
        foreach ($modules as $module) {
            $oneModule->install($module);
        }

        return $oneModule;
    }
}
