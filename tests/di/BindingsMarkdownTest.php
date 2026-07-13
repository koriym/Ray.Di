<?php

declare(strict_types=1);

namespace Ray\Di;

use Closure;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function assert;
use function file_get_contents;
use function glob;
use function is_dir;
use function is_string;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

class BindingsMarkdownTest extends TestCase
{
    private string $classDir;

    protected function setUp(): void
    {
        $this->classDir = sys_get_temp_dir() . '/ray-bindings-md-' . uniqid('', true);
        if (! mkdir($this->classDir) && ! is_dir($this->classDir)) {
            throw new RuntimeException('Cannot create ' . $this->classDir);
        }
    }

    protected function tearDown(): void
    {
        // remove the whole test-owned dir: bindings.md and any generated proxies
        foreach (glob($this->classDir . '/*') ?: [] as $artifact) {
            unlink($artifact);
        }

        if (is_dir($this->classDir)) {
            rmdir($this->classDir);
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

        // title + summary counts, then Bindings first
        $this->assertStringStartsWith(
            "# Ray.Di bindings\n\n15 bindings · 5 modules · 0 replaced · 0 discarded\n\n## Bindings\n\n",
            $markdown,
        );
        // bindings list the resolved target
        $this->assertStringContainsString(FakeAopInterface::class . '- => (dependency) ' . FakeAop::class, $markdown);
        // modules, sorted, with each module's binding count, then provenance
        $this->assertStringContainsString(
            "## Modules\n\n"
            . "- Ray\\Di\\AssistedInjectModule (1)\n"
            . "- Ray\\Di\\AssistedModule (2)\n"
            . '- ' . FakeLogStringModule::class . " (9)\n"
            . "- Ray\\Di\\MultiBinding\\MultiBindingModule (2)\n"
            . "- Ray\\Di\\ProviderSetModule (1)\n\n## Provenance",
            $markdown,
        );
        // provenance names the module that bound it
        $this->assertStringContainsString('@' . FakeLogStringModule::class, $markdown);
        $this->assertStringEndsWith("\n", $markdown);
    }

    /**
     * The summary counts replaced (last-write-wins rebind) and discarded
     * (merge collision, incoming dropped) bindings.
     */
    public function testSummaryCountsReplacedAndDiscarded(): void
    {
        new Injector(new FakeBindingLogModule(new FakeBindingLogInnerModule()), $this->classDir);

        $markdown = file_get_contents($this->classDir . '/bindings.md');
        assert(is_string($markdown));

        $this->assertStringContainsString('8 bindings · 6 modules · 1 replaced · 1 discarded', $markdown);
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
