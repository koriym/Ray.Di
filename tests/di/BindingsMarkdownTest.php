<?php

declare(strict_types=1);

namespace Ray\Di;

use Closure;
use PHPUnit\Framework\TestCase;

use function assert;
use function file_exists;
use function file_get_contents;
use function is_string;
use function mkdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

class BindingsMarkdownTest extends TestCase
{
    private string $classDir;

    protected function setUp(): void
    {
        $this->classDir = sys_get_temp_dir() . '/ray-bindings-md-' . uniqid();
        mkdir($this->classDir);
    }

    protected function tearDown(): void
    {
        $file = $this->classDir . '/bindings.md';
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Building an injector emits bindings.md next to the generated classes,
     * with the provenance log and the resolved bindings.
     */
    public function testInjectorEmitsBindingsMarkdown(): void
    {
        new Injector(new FakeLogStringModule(), $this->classDir);

        $markdown = file_get_contents($this->classDir . '/bindings.md');
        assert(is_string($markdown));

        $this->assertStringContainsString('## Provenance', $markdown);
        $this->assertStringContainsString('## Bindings', $markdown);
        // provenance names the module that bound it
        $this->assertStringContainsString('@' . FakeLogStringModule::class, $markdown);
        // bindings list the resolved target
        $this->assertStringContainsString(FakeAopInterface::class . '- => (dependency) ' . FakeAop::class, $markdown);
    }

    /**
     * A binding ModuleString cannot serialize (a closure instance) makes the
     * emission fail, but construction must still succeed — the diagnostics
     * artifact is best-effort.
     */
    public function testConstructionSucceedsWhenMarkdownCannotBeWritten(): void
    {
        $injector = new Injector(new FakeClosureBindModule(), $this->classDir);

        $this->assertInstanceOf(Closure::class, $injector->getInstance('', 'callback'));
        $this->assertFileDoesNotExist($this->classDir . '/bindings.md');
    }
}
