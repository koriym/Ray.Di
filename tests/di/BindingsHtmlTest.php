<?php

declare(strict_types=1);

namespace Ray\Di;

use PHPUnit\Framework\TestCase;
use RuntimeException;

use function array_merge;
use function assert;
use function dirname;
use function fclose;
use function file_get_contents;
use function fwrite;
use function glob;
use function html_entity_decode;
use function is_dir;
use function is_resource;
use function is_string;
use function mkdir;
use function preg_match;
use function proc_close;
use function proc_open;
use function rmdir;
use function stream_get_contents;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const ENT_QUOTES;
use const PHP_BINARY;

/**
 * Covers Ray\Di\BindingsHtml and its bin/bindings-html CLI
 *
 * The renderer is a projection, not a source of truth: it embeds the markdown
 * verbatim in a <pre> data island and references a shared viewer (stylesheet +
 * script) from the CDN, so every consumer reuses one implementation. The class
 * is unit-tested directly; the CLI is exercised as a subprocess so argument
 * handling, stdin, and exit codes are covered too.
 */
class BindingsHtmlTest extends TestCase
{
    private string $bin;
    private string $classDir;
    private string $md;

    protected function setUp(): void
    {
        $this->bin = dirname(__DIR__, 2) . '/bin/bindings-html';
        $this->classDir = sys_get_temp_dir() . '/ray-bindings-html-' . uniqid('', true);
        if (! mkdir($this->classDir) && ! is_dir($this->classDir)) {
            throw new RuntimeException('Cannot create ' . $this->classDir);
        }

        // a real bindings.md, emitted by the framework itself
        new Injector(new FakeLogStringModule(), $this->classDir);
        $this->md = $this->classDir . '/bindings.md';
    }

    protected function tearDown(): void
    {
        foreach (glob($this->classDir . '/*') ?: [] as $artifact) {
            unlink($artifact);
        }

        if (is_dir($this->classDir)) {
            rmdir($this->classDir);
        }
    }

    public function testFragmentEmbedsTheMarkdownVerbatim(): void
    {
        $markdown = $this->markdown();

        $fragment = (new BindingsHtml())->fragment($markdown);

        // the <pre> data island unescapes back to the exact markdown
        $this->assertSame(1, preg_match('#<pre id="src">(.*)</pre>#s', $fragment, $matches));
        $this->assertSame($markdown, html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
        $this->assertStringContainsString('<div id="view"></div>', $fragment);
    }

    public function testPageLinksTheSharedCdnAssets(): void
    {
        $page = (new BindingsHtml())->page($this->markdown());

        $this->assertStringContainsString('<link rel="stylesheet" href="' . BindingsHtml::CSS_URL . '">', $page);
        $this->assertStringContainsString('<script src="' . BindingsHtml::JS_URL . '">', $page);
        $this->assertStringContainsString('<pre id="src">', $page);
    }

    public function testPageRendersTheOptionalMessageAsSubtitle(): void
    {
        $html = new BindingsHtml();

        $this->assertStringContainsString('<div class="sub">prod-app</div>', $html->page($this->markdown(), 'prod-app'));
        $this->assertStringNotContainsString('class="sub"', $html->page($this->markdown()));
    }

    public function testCliRendersThePageFromAFile(): void
    {
        [$stdout, , $exit] = $this->runTool([$this->md]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString(BindingsHtml::JS_URL, $stdout);
        $this->assertSame(1, preg_match('#<pre id="src">(.*)</pre>#s', $stdout, $matches));
        $this->assertSame($this->markdown(), html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
    }

    public function testCliReadsMarkdownFromStdin(): void
    {
        [$stdout, , $exit] = $this->runTool(['-'], $this->markdown());

        $this->assertSame(0, $exit);
        $this->assertSame(1, preg_match('#<pre id="src">(.*)</pre>#s', $stdout, $matches));
        $this->assertSame($this->markdown(), html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
    }

    public function testCliExitsNonZeroWhenTheMarkdownCannotBeRead(): void
    {
        [, $stderr, $exit] = $this->runTool([$this->classDir . '/missing.md']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('cannot read', $stderr);
    }

    private function markdown(): string
    {
        $markdown = file_get_contents($this->md);
        assert(is_string($markdown));

        return $markdown;
    }

    /**
     * Invoke the CLI as a subprocess.
     *
     * @param list<string> $args
     *
     * @return array{string, string, int} stdout, stderr, exit code
     */
    private function runTool(array $args, string $stdin = ''): array
    {
        $spec = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
        $process = proc_open(array_merge([PHP_BINARY, $this->bin], $args), $spec, $pipes);
        assert(is_resource($process));

        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        return [(string) $stdout, (string) $stderr, $exit];
    }
}
