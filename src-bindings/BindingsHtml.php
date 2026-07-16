<?php

declare(strict_types=1);

namespace Ray\Bindings;

use JsonException;
use Throwable;

use function array_merge;
use function array_unique;
use function count;
use function htmlspecialchars;
use function is_array;
use function is_dir;
use function is_file;
use function json_decode;
use function json_encode;
use function preg_match_all;
use function preg_replace;
use function rtrim;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;
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
 * no vendor/ access is required for the basic map; when $vendorDir is given,
 * shared PSR-4 prefixes (e.g. bear/package and bear/aura-router-module both
 * claim BEAR\Package\) are disambiguated by checking which package actually
 * contains each class file mentioned in the markdown.
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
 * @psalm-type SourceMapEntry = array{
 *     p: string,
 *     u: string,
 *     r: string,
 *     n: string,
 *     d: string,
 *     path?: string,
 *     x?: 1
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
     *
     * @param non-empty-string|'' $vendorDir Absolute path to composer vendor/ for prefix disambiguation
     */
    public function fragment(string $bindingsMarkdown, string $composerLock = '', string $vendorDir = ''): string
    {
        $md = htmlspecialchars($bindingsMarkdown, ENT_QUOTES, 'UTF-8');

        return '<pre id="src">' . $md . '</pre>' . "\n" . '<div id="view"></div>'
            . $this->sourceMap($bindingsMarkdown, $composerLock, $vendorDir);
    }

    /**
     * A standalone page around {@see fragment()}, linking the shared viewer
     * assets from the CDN — used by bin/bindings-html.
     *
     * The optional $message renders as a subtitle, e.g. the context the
     * bindings were composed in ("prod-app").
     *
     * @param non-empty-string|'' $vendorDir Absolute path to composer vendor/ for prefix disambiguation
     */
    public function page(string $bindingsMarkdown, string $composerLock = '', string $message = '', string $vendorDir = ''): string
    {
        $fragment = $this->fragment($bindingsMarkdown, $composerLock, $vendorDir);
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
     * from composer.lock and pruned to the packages actually named in the
     * markdown. When $vendorDir is provided, FQCNs that match multiple packages
     * under the same prefix get an exact-class override entry with a "path" key.
     */
    private function sourceMap(string $markdown, string $composerLock, string $vendorDir = ''): string
    {
        if ($composerLock === '') {
            return '';
        }

        try {
            return $this->buildSourceMap($markdown, $composerLock, $vendorDir);
        } catch (Throwable) {
            // a malformed lock (bad JSON or unexpected shapes) omits the source
            // map rather than crashing the render — the page still works
            return '';
        }
    }

    /** @throws JsonException on invalid JSON in the composer.lock. */
    private function buildSourceMap(string $markdown, string $composerLock, string $vendorDir = ''): string
    {
        $decoded = json_decode($composerLock, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            return '';
        }

        /** @var array{packages?: list<ComposerPackage>, packages-dev?: list<ComposerPackage>} $decoded */
        $packages = array_merge($decoded['packages'] ?? [], $decoded['packages-dev'] ?? []);

        /** @var list<SourceMapEntry> $map */
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

        if ($vendorDir !== '' && is_dir($vendorDir)) {
            $map = $this->disambiguateSharedPrefixes($map, $markdown, $vendorDir);
        }

        return "\n" . '<script type="application/json" id="srcmap">'
            . (string) json_encode($map, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * When two packages share a PSR-4 prefix, add exact-class entries (longer "p")
     * with an explicit path for each FQCN in the markdown that exists on disk.
     *
     * @param list<SourceMapEntry> $map
     *
     * @return list<SourceMapEntry>
     */
    private function disambiguateSharedPrefixes(array $map, string $markdown, string $vendorDir): array
    {
        $byPrefix = [];
        foreach ($map as $entry) {
            $byPrefix[$entry['p']][] = $entry;
        }

        $ambiguous = [];
        foreach ($byPrefix as $prefix => $entries) {
            if (count($entries) > 1) {
                $ambiguous[$prefix] = $entries;
            }
        }

        if ($ambiguous === []) {
            return $map;
        }

        preg_match_all('/[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z0-9_]+)+/', $markdown, $matches);
        $fqcns = array_unique($matches[0]);
        foreach ($fqcns as $fqcn) {
            $bestLen = -1;
            /** @var list<SourceMapEntry> $candidates */
            $candidates = [];
            foreach ($map as $entry) {
                // Ignore exact-class overrides already added (path/x) — a shorter class
                // name must not act as a prefix of a longer one (AuraRouter vs Module).
                if (isset($entry['path']) || isset($entry['x'])) {
                    continue;
                }

                if (! str_starts_with($fqcn, $entry['p'])) {
                    continue;
                }

                $len = strlen($entry['p']);
                if ($len > $bestLen) {
                    $bestLen = $len;
                    $candidates = [$entry];
                    continue;
                }

                if ($len === $bestLen) {
                    $candidates[] = $entry;
                }
            }

            if (count($candidates) < 2 || ! isset($ambiguous[$candidates[0]['p']])) {
                continue;
            }

            $rel = str_replace('\\', '/', substr($fqcn, $bestLen)) . '.php';
            foreach ($candidates as $candidate) {
                $path = $candidate['d'] === '' ? $rel : $candidate['d'] . '/' . $rel;
                $full = $vendorDir . '/' . $candidate['n'] . '/' . $path;
                if (! is_file($full)) {
                    continue;
                }

                // Exact class match (x=1) so shorter class names are not prefixes of longer ones
                // (e.g. AuraRouter must not steal AuraRouterModule).
                $map[] = [
                    'p' => $fqcn,
                    'u' => $candidate['u'],
                    'r' => $candidate['r'],
                    'n' => $candidate['n'],
                    'd' => $candidate['d'],
                    'path' => $path,
                    'x' => 1,
                ];
                break;
            }
        }

        return $map;
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
