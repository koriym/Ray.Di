<?php

declare(strict_types=1);

namespace Ray\Bindings;

use Closure;
use PHPUnit\Framework\TestCase;
use Ray\Aop\Matcher;
use Ray\Aop\Pointcut;
use Ray\Di\AbstractModule;
use Ray\Di\Bind;
use Ray\Di\BindingsMarkdown as LegacyBindingsMarkdown;
use Ray\Di\Container;
use Ray\Di\FakeAop;
use Ray\Di\FakeAopInterface;
use Ray\Di\FakeBindingLogInnerModule;
use Ray\Di\FakeBindingLogModule;
use Ray\Di\FakeClosureBindModule;
use Ray\Di\FakeCountingMatcher;
use Ray\Di\FakeDoubleInterceptor;
use Ray\Di\FakeEngine;
use Ray\Di\FakeLogStringModule;
use Ray\Di\FakeToBindModule;
use Ray\Di\Injector;
use RuntimeException;

use function assert;
use function file_get_contents;
use function file_put_contents;
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

    /** The explicit writer preserves the existing markdown format. */
    public function testExplicitWriterEmitsBindingsMarkdown(): void
    {
        $writer = new BindingsMarkdown();
        $writer((new FakeLogStringModule())->getContainer(), $this->classDir);

        $markdown = file_get_contents($this->classDir . '/bindings.md');
        assert(is_string($markdown));

        // title + summary counts, then Bindings first
        $this->assertStringStartsWith(
            "# Ray.Di bindings\n\n9 bindings · 1 modules · 0 replaced · 0 discarded\n\n## Bindings\n\n",
            $markdown,
        );
        // bindings list the resolved target
        $this->assertStringContainsString(FakeAopInterface::class . '- => (dependency) ' . FakeAop::class, $markdown);
        // modules, sorted, with each module's binding count, then provenance
        $this->assertStringContainsString(
            "## Modules\n\n"
            . '- ' . FakeLogStringModule::class . " (9)\n\n## Provenance",
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
        $writer = new BindingsMarkdown();
        $writer((new FakeBindingLogModule(new FakeBindingLogInnerModule()))->getContainer(), $this->classDir);

        $markdown = file_get_contents($this->classDir . '/bindings.md');
        assert(is_string($markdown));

        $this->assertStringContainsString('2 bindings · 2 modules · 1 replaced · 1 discarded', $markdown);
    }

    /** A closure binding is rendered without serializing the container. */
    public function testClosureBindingIsWrittenToMarkdown(): void
    {
        $module = new FakeClosureBindModule();
        (new BindingsMarkdown())($module->getContainer(), $this->classDir);
        $injector = new Injector($module, $this->classDir);

        $this->assertInstanceOf(Closure::class, $injector->getInstance('', 'callback'));
        $markdown = file_get_contents($this->classDir . '/bindings.md');
        assert(is_string($markdown));
        $this->assertStringContainsString('-callback => (object) Closure', $markdown);
    }

    /**
     * A concrete class bound to itself (untargeted) is collapsed to
     * '(untargeted)' in the Bindings section, matching the provenance label so
     * the resolved bindings and the provenance agree.
     */
    public function testUntargetedBindingIsCollapsedInTheBindingsSection(): void
    {
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(FakeEngine::class);
            }
        };
        (new BindingsMarkdown())($module->getContainer(), $this->classDir);

        $markdown = file_get_contents($this->classDir . '/bindings.md');
        assert(is_string($markdown));

        $this->assertStringContainsString(FakeEngine::class . '- => (untargeted)', $markdown);
    }

    public function testInjectorDoesNotEmitBindingsArtifacts(): void
    {
        new Injector(new FakeToBindModule(), $this->classDir);

        $this->assertFileDoesNotExist($this->classDir . '/bindings.md');
        $this->assertFileDoesNotExist($this->classDir . '/bindings.md.signature');
    }

    public function testWriterUsesThePublicRenderer(): void
    {
        $container = (new FakeLogStringModule())->getContainer();
        $writer = new BindingsMarkdown();
        $rendered = $writer->render($container);

        $writer($container, $this->classDir);

        $this->assertSame($rendered, file_get_contents($this->classDir . '/bindings.md'));
    }

    public function testLegacyWriterDelegatesToBindingsWriter(): void
    {
        $container = (new FakeLogStringModule())->getContainer();
        $legacyWriter = new LegacyBindingsMarkdown();

        $this->assertSame((new BindingsMarkdown())->render($container), $legacyWriter->render($container));

        $legacyWriter($container, $this->classDir);

        $this->assertFileExists($this->classDir . '/bindings.md');
    }

    /** An unchanged binding surface reuses the existing markdown. */
    public function testUnchangedBindingsUseSignatureCache(): void
    {
        $container = (new FakeLogStringModule())->getContainer();
        $writer = new BindingsMarkdown();
        $writer($container, $this->classDir);
        $this->assertFileExists($this->classDir . '/bindings.md.signature');
        file_put_contents($this->classDir . '/bindings.md', 'cached');

        $writer($container, $this->classDir);

        $this->assertSame('cached', file_get_contents($this->classDir . '/bindings.md'));
    }

    /** A changed resolved binding invalidates the cached markdown. */
    public function testBindingChangeInvalidatesSignatureCache(): void
    {
        $container = new Container();
        (new Bind($container, '', self::class))->annotatedWith('value')->toInstance(1);
        $writer = new BindingsMarkdown();
        $writer($container, $this->classDir);

        (new Bind($container, '', self::class))->annotatedWith('value')->toInstance(2);
        $writer($container, $this->classDir);

        $markdown = file_get_contents($this->classDir . '/bindings.md');
        assert(is_string($markdown));
        $this->assertStringContainsString('-value => (integer) 2', $markdown);
    }

    /** Provenance-only changes intentionally retain the cached markdown. */
    public function testProvenanceChangeDoesNotInvalidateSignatureCache(): void
    {
        $first = new Container();
        (new Bind($first, '', 'FirstModule'))->annotatedWith('value')->toInstance(1);
        $second = new Container();
        (new Bind($second, '', 'SecondModule'))->annotatedWith('value')->toInstance(1);
        $writer = new BindingsMarkdown();
        $writer($first, $this->classDir);

        $writer($second, $this->classDir);

        $markdown = file_get_contents($this->classDir . '/bindings.md');
        assert(is_string($markdown));
        $this->assertStringContainsString('@FirstModule', $markdown);
        $this->assertStringNotContainsString('@SecondModule', $markdown);
    }

    /** Matcher runtime state is not part of the declarative pointcut signature. */
    public function testMatcherRuntimeStateDoesNotInvalidateSignatureCache(): void
    {
        $container = new Container();
        (new Bind($container, FakeAopInterface::class, self::class))->to(FakeAop::class);
        $classMatcher = new FakeCountingMatcher();
        $container->addPointcut(new Pointcut($classMatcher, (new Matcher())->any(), [FakeDoubleInterceptor::class]));
        $writer = new BindingsMarkdown();
        $writer($container, $this->classDir);
        $matches = $classMatcher->matches;
        file_put_contents($this->classDir . '/bindings.md', 'cached');

        $writer($container, $this->classDir);

        $this->assertSame($matches, $classMatcher->matches);
        $this->assertSame('cached', file_get_contents($this->classDir . '/bindings.md'));
    }
}
