<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Aop\Compiler;
use Ray\Di\Exception\DirectoryNotWritable;
use Ray\Di\Exception\Unbound;
use Ray\Di\Exception\Untargeted;

use function assert;
use function file_exists;
use function is_dir;
use function is_writable;
use function spl_autoload_register;
use function sprintf;
use function str_replace;
use function sys_get_temp_dir;

/**
 * @psalm-import-type BindableInterface from Types
 * @psalm-import-type ModuleList from Types
 * @psalm-import-type ScriptDir from Types
 */
final class Injector implements InjectorInterface
{
    /** @var ScriptDir */
    private readonly string $classDir;
    private readonly Container $container;

    /**
     * @param AbstractModule|ModuleList|null $module Module(s)
     * @param string                         $tmpDir Temp directory for generated class
     */
    public function __construct($module = null, string $tmpDir = '')
    {
        /** @var ScriptDir $classDir */
        $classDir = is_dir($tmpDir) ? $tmpDir : sys_get_temp_dir();
        if (! is_writable($classDir)) {
            throw new DirectoryNotWritable($classDir); // @codeCoverageIgnore
        }

        $this->classDir = $classDir;
        $this->container = (new ContainerFactory())($module, $this->classDir);
        $this->container->setSource(self::class); // builtin + JIT bindings attribute to the Injector
        // Bind injector (built-in bindings)
        (new Bind($this->container, InjectorInterface::class))->toInstance($this);
        $this->container->sort();
    }

    /**
     * Wakeup
     */
    public function __wakeup()
    {
        // the binding log's source is not serialized; restore it so JIT
        // bindings by a cached injector keep attributing to the Injector
        $this->container->setSource(self::class);
        spl_autoload_register(
            function (string $class): void {
                $file = sprintf('%s/%s.php', $this->classDir, str_replace('\\', '_', $class));
                if (file_exists($file)) {
                    include $file; //@codeCoverageIgnore
                }
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getInstance($interface, $name = Name::ANY)
    {
        try {
            /** @psalm-suppress MixedAssignment */
            $instance = $this->container->getInstance($interface, $name);
        } catch (Untargeted $untargeted) {
            // Just-in-time binding registers the class under Name::ANY only, so a
            // named request can never be satisfied by it and would retry forever.
            if ($name !== Name::ANY) {
                throw new Unbound(sprintf("'%s-%s'", $interface, $name), 0, $untargeted);
            }

            /**
             * @psalm-var class-string $interface
             * @psalm-suppress MixedAssignment
             */
            $instance = $this->bind($interface);
        }

        /** @psalm-suppress MixedReturnStatement */
        return $instance;
    }

    /**
     * @param BindableInterface $class
     *
     * @return mixed
     */
    private function bind(string $class)
    {
        new Bind($this->container, $class);
        $bound = $this->container->getContainer()[$class . '-' . Name::ANY];
        assert($bound instanceof Dependency);

        /** @psalm-suppress InvalidArgument */
        return $this->container->weaveAspect(new Compiler($this->classDir), $bound)->getInstance($class);
    }
}
