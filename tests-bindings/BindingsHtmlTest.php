<?php

declare(strict_types=1);

namespace Ray\Bindings;

use PHPUnit\Framework\TestCase;
use RuntimeException;

use function array_merge;
use function assert;
use function dirname;
use function fclose;
use function file_put_contents;
use function fwrite;
use function glob;
use function html_entity_decode;
use function is_dir;
use function is_resource;
use function json_encode;
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
 * Covers Ray\Bindings\BindingsHtml and its bin/bindings-html CLI
 *
 * BindingsHtml is a pure markdown-to-HTML renderer with no dependency on the DI
 * container, so the tests feed it a fixed markdown fixture rather than a
 * live-generated bindings.md. It embeds the markdown verbatim in a <pre> data
 * island and references a shared viewer from the CDN; the class is unit-tested
 * directly and the CLI is exercised as a subprocess so argument handling,
 * stdin, and exit codes are covered too.
 */
class BindingsHtmlTest extends TestCase
{
    /** A minimal bindings.md whose namespaces match {@see COMPOSER_LOCK}. */
    private const MARKDOWN = <<<'MD'
        # Ray.Di bindings

        3 bindings · 2 modules · 0 replaced · 0 discarded

        ## Bindings

        Ray\Di\FakeEngineInterface- => (dependency) Ray\Di\FakeEngine
        Ray\Di\FakeRobotInterface- => (dependency) Ray\Di\FakeRobot
        Acme\Shop\CartInterface- => (dependency) Acme\Shop\Cart

        ## Modules

        - Ray\Di\FakeModule (2)
        - Acme\Shop\ShopModule (1)

        ## Provenance

        bind    Ray\Di\FakeEngineInterface- => (dependency) Ray\Di\FakeEngine @Ray\Di\FakeModule
        bind    Ray\Di\FakeRobotInterface- => (dependency) Ray\Di\FakeRobot @Ray\Di\FakeModule
        bind    Acme\Shop\CartInterface- => (dependency) Acme\Shop\Cart @Acme\Shop\ShopModule
        MD;

    private string $bin;
    private string $dir;
    private string $md;

    protected function setUp(): void
    {
        $this->bin = dirname(__DIR__) . '/bin/bindings-html';
        $this->dir = sys_get_temp_dir() . '/ray-bindings-html-' . uniqid('', true);
        if (! mkdir($this->dir) && ! is_dir($this->dir)) {
            throw new RuntimeException('Cannot create ' . $this->dir);
        }

        $this->md = $this->dir . '/bindings.md';
        file_put_contents($this->md, self::MARKDOWN);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->dir)) {
            rmdir($this->dir);
        }
    }

    public function testFragmentEmbedsTheMarkdownVerbatim(): void
    {
        $fragment = (new BindingsHtml())->fragment(self::MARKDOWN);

        $this->assertSame(1, preg_match('#<pre id="src">(.*)</pre>#s', $fragment, $matches));
        $this->assertSame(self::MARKDOWN, html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
        $this->assertStringContainsString('<div id="view"></div>', $fragment);
    }

    public function testPageLinksTheSharedCdnAssets(): void
    {
        $page = (new BindingsHtml())->page(self::MARKDOWN);

        $this->assertStringContainsString('<link rel="stylesheet" href="' . BindingsHtml::CSS_URL . '">', $page);
        $this->assertStringContainsString('<script src="' . BindingsHtml::JS_URL . '">', $page);
        $this->assertStringContainsString('<pre id="src">', $page);
    }

    public function testPageRendersTheOptionalMessageAsSubtitle(): void
    {
        $html = new BindingsHtml();

        $this->assertStringContainsString('<div class="sub">prod-app</div>', $html->page(self::MARKDOWN, '', 'prod-app'));
        $this->assertStringNotContainsString('class="sub"', $html->page(self::MARKDOWN));
    }

    public function testEmbedsASourceMapDerivedFromComposerLock(): void
    {
        $page = (new BindingsHtml())->page(self::MARKDOWN, $this->composerLock());

        $this->assertStringContainsString('<script type="application/json" id="srcmap">', $page);
        $this->assertStringContainsString('"u":"https://github.com/ray-di/Ray.Di"', $page);
        $this->assertStringContainsString('"n":"ray/di"', $page);
        $this->assertStringContainsString('"r":"abcdef1234"', $page);
        $this->assertStringContainsString('"d":"src/di"', $page);
    }

    public function testOmitsTheSourceMapWithoutAComposerLock(): void
    {
        $this->assertStringNotContainsString('id="srcmap"', (new BindingsHtml())->page(self::MARKDOWN));
    }

    public function testMalformedComposerLockOmitsTheSourceMapWithoutCrashing(): void
    {
        $html = new BindingsHtml();

        // invalid JSON and an unexpected shape both degrade to no source map
        $this->assertStringNotContainsString('id="srcmap"', $html->page(self::MARKDOWN, 'not json'));
        $this->assertStringNotContainsString('id="srcmap"', $html->page(self::MARKDOWN, '{"packages":"not-an-array"}'));
    }

    public function testCliRendersThePageFromAFile(): void
    {
        [$stdout, , $exit] = $this->runTool([$this->md]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString(BindingsHtml::JS_URL, $stdout);
        $this->assertSame(1, preg_match('#<pre id="src">(.*)</pre>#s', $stdout, $matches));
        $this->assertSame(self::MARKDOWN, html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
    }

    public function testCliReadsMarkdownFromStdin(): void
    {
        [$stdout, , $exit] = $this->runTool(['-'], self::MARKDOWN);

        $this->assertSame(0, $exit);
        $this->assertSame(1, preg_match('#<pre id="src">(.*)</pre>#s', $stdout, $matches));
        $this->assertSame(self::MARKDOWN, html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
    }

    public function testCliEmbedsTheSourceMapWhenGivenAComposerLock(): void
    {
        $lock = $this->dir . '/composer.lock';
        file_put_contents($lock, $this->composerLock());

        [$stdout, , $exit] = $this->runTool([$this->md, $lock]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('id="srcmap"', $stdout);
        $this->assertStringContainsString('"n":"ray/di"', $stdout);
    }

    public function testCliExitsNonZeroWhenTheMarkdownCannotBeRead(): void
    {
        [, $stderr, $exit] = $this->runTool([$this->dir . '/missing.md']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('cannot read', $stderr);
    }

    /** A composer.lock whose one package's namespace (Ray\Di\) appears in the markdown. */
    private function composerLock(): string
    {
        return (string) json_encode([
            'packages' => [
                [
                    'name' => 'ray/di',
                    'source' => ['type' => 'git', 'url' => 'https://github.com/ray-di/Ray.Di.git', 'reference' => 'abcdef1234'],
                    'autoload' => ['psr-4' => ['Ray\\Di\\' => 'src/di']],
                ],
            ],
        ]);
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
