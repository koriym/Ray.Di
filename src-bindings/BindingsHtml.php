<?php

declare(strict_types=1);

namespace Ray\Bindings;

use JsonException;
use Throwable;

use function array_merge;
use function htmlspecialchars;
use function is_array;
use function json_decode;
use function json_encode;
use function preg_replace;
use function rtrim;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function substr;

use const ENT_QUOTES;
use const JSON_HEX_TAG;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/**
 * Renders a Ray.Di bindings.md as an HTML view
 *
 * The markdown is embedded verbatim in a <pre> data island; a small viewer
 * script decorates it on load — colour-coded events, a type filter, and the
 * discarded side of every collision shown in red. The stylesheet and script
 * are shared assets served from a CDN, so every consumer — the bin/bindings-html
 * CLI, a documentation generator, a hand-written page — reuses the same viewer
 * instead of embedding its own copy.
 *
 * Given a composer.lock the viewer also links each class to its source: the
 * lock's per-package source URL + reference resolve to a GitHub blob URL (for
 * humans), and the package name resolves to the local vendor/ path (carried in
 * data-src, so an agent working in the project reads the file directly instead
 * of fetching over the network). Resolution is pure string work — no reflection,
 * no vendor/ access needed at render time.
 *
 * {@see fragment()} is the reusable core (the data island, the mount point, and
 * the optional source map) for embedding in a page that owns its own <head>;
 * {@see page()} wraps it in a standalone document that links the CDN assets.
 * Without JavaScript the <pre> shows the plain log.
 *
 * @psalm-type ComposerPackage = array{
 *     name?: string,
 *     source?: array{url?: string, reference?: string},
 *     autoload?: array{psr-4?: array<string, string|list<string>>}
 * }
 */
final class BindingsHtml
{
    private const VERSION = '2.x';
    public const CSS_URL = 'https://cdn.jsdelivr.net/gh/ray-di/Ray.Di@' . self::VERSION . '/docs/bindings/bindings.css';
    public const JS_URL = 'https://cdn.jsdelivr.net/gh/ray-di/Ray.Di@' . self::VERSION . '/docs/bindings/bindings.js';

    /**
     * The reusable core: the markdown as a <pre> data island, the mount point
     * the viewer decorates, and — when a composer.lock is given — the source
     * map it links classes with. Embed this in a page that references
     * {@see CSS_URL} and {@see JS_URL}.
     */
    public function fragment(string $bindingsMarkdown, string $composerLock = ''): string
    {
        $md = htmlspecialchars($bindingsMarkdown, ENT_QUOTES, 'UTF-8');

        return '<pre id="src">' . $md . '</pre>' . "\n" . '<div id="view"></div>'
            . $this->sourceMap($bindingsMarkdown, $composerLock);
    }

    /**
     * A standalone page around {@see fragment()}, linking the shared viewer
     * assets from the CDN — used by bin/bindings-html.
     *
     * The optional $message renders as a subtitle, e.g. the context the
     * bindings were composed in ("prod-app").
     */
    public function page(string $bindingsMarkdown, string $composerLock = '', string $message = ''): string
    {
        $fragment = $this->fragment($bindingsMarkdown, $composerLock);
        $sub = $message === ''
            ? ''
            : "\n<div class=\"sub\">" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
        $css = self::CSS_URL;
        $js = self::JS_URL;

        return <<<HTML
            <!doctype html>
            <html lang="en">
            <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Ray.Di bindings</title>
            <link rel="stylesheet" href="$css">
            </head>
            <body>
            <div class="wrap">
            <header>
            <h1>Ray.Di bindings</h1>$sub
            </header>
            $fragment
            </div>
            <script src="$js"></script>
            </body>
            </html>

            HTML;
    }

    /**
     * A <script id="srcmap"> table the viewer uses to link classes to source.
     *
     * Each entry maps a PSR-4 prefix to its package's repository URL, commit
     * reference, package name, and source directory — everything the viewer
     * needs to build both a GitHub blob URL and a local vendor/ path. Derived
     * entirely from composer.lock and pruned to the packages actually named in
     * the markdown, so the payload stays small.
     */
    private function sourceMap(string $markdown, string $composerLock): string
    {
        if ($composerLock === '') {
            return '';
        }

        try {
            return $this->buildSourceMap($markdown, $composerLock);
        } catch (Throwable) {
            // a malformed lock (bad JSON or unexpected shapes) omits the source
            // map rather than crashing the render — the page still works
            return '';
        }
    }

    /** @throws JsonException on invalid JSON in the composer.lock. */
    private function buildSourceMap(string $markdown, string $composerLock): string
    {
        $decoded = json_decode($composerLock, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            return '';
        }

        /** @var array{packages?: list<ComposerPackage>, packages-dev?: list<ComposerPackage>} $decoded */
        $packages = array_merge($decoded['packages'] ?? [], $decoded['packages-dev'] ?? []);

        $map = [];
        foreach ($packages as $package) {
            $name = $package['name'] ?? null;
            $source = $package['source'] ?? [];
            $autoload = $package['autoload'] ?? [];
            $psr4 = $autoload['psr-4'] ?? null;
            $url = $source['url'] ?? null;
            $reference = $source['reference'] ?? null;
            if ($name === null || $url === null || $reference === null || $psr4 === null) {
                continue;
            }

            $repository = $this->repositoryUrl($url);
            foreach ($psr4 as $prefix => $dir) {
                // prune: keep only packages whose namespace appears in the bindings
                if ($prefix === '' || ! str_contains($markdown, $prefix)) {
                    continue;
                }

                $sourceDir = is_array($dir) ? ($dir[0] ?? null) : $dir;
                if ($sourceDir === null) {
                    continue;
                }

                $map[] = ['p' => $prefix, 'u' => $repository, 'r' => $reference, 'n' => $name, 'd' => rtrim($sourceDir, '/')];
            }
        }

        if ($map === []) {
            return '';
        }

        return "\n" . '<script type="application/json" id="srcmap">'
            . (string) json_encode($map, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /** Normalise a composer source URL to its web repository URL. */
    private function repositoryUrl(string $url): string
    {
        // git@github.com:owner/repo.git -> https://github.com/owner/repo
        if (str_starts_with($url, 'git@')) {
            $url = 'https://' . str_replace(':', '/', substr($url, 4));
        }

        return (string) preg_replace('#\.git$#', '', $url);
    }
}
