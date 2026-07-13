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
 * Smoke-tests the bin/bindings-html CLI against a real bindings.md
 *
 * The tool is a projection, not a source of truth: it must embed the markdown
 * verbatim — so a committed bindings.html diffs exactly like the plain
 * markdown — and ship its decoration inline. These tests invoke the script the
 * way a user does, as a subprocess, so argument handling, stdin, and exit
 * codes are covered as well.
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

    public function testEmbedsTheMarkdownVerbatimAndShipsTheDecoration(): void
    {
        [$stdout, , $exit] = $this->runTool([$this->md]);

        $this->assertSame(0, $exit);
        // the <pre> data island unescapes back to the exact markdown
        $this->assertSame(1, preg_match('#<pre id="src">(.*)</pre>#s', $stdout, $matches));
        $recovered = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        $original = file_get_contents($this->md);
        assert(is_string($original));
        $this->assertSame($original, $recovered);
        // the decoration travels in the same file
        $this->assertStringContainsString('function renderEvent', $stdout);
        $this->assertStringContainsString('<div id="view"></div>', $stdout);
    }

    public function testOptionalMessageBecomesASubtitle(): void
    {
        [$stdout] = $this->runTool([$this->md, 'prod-app']);

        $this->assertStringContainsString('<div class="sub">prod-app</div>', $stdout);
    }

    public function testReadsMarkdownFromStdin(): void
    {
        $md = file_get_contents($this->md);
        assert(is_string($md));

        [$stdout, , $exit] = $this->runTool(['-'], $md);

        $this->assertSame(0, $exit);
        $this->assertSame(1, preg_match('#<pre id="src">(.*)</pre>#s', $stdout, $matches));
        $this->assertSame($md, html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
    }

    public function testExitsNonZeroWhenTheMarkdownCannotBeRead(): void
    {
        [, $stderr, $exit] = $this->runTool([$this->classDir . '/missing.md']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('cannot read', $stderr);
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
