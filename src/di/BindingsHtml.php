<?php

declare(strict_types=1);

namespace Ray\Di;

use function htmlspecialchars;

use const ENT_QUOTES;

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
 * {@see fragment()} is the reusable core (the data island plus the mount point)
 * for embedding in a page that owns its own <head>; {@see page()} wraps it in a
 * standalone document that links the CDN assets. Without JavaScript the <pre>
 * shows the plain log.
 */
final class BindingsHtml
{
    private const VERSION = '2.x';
    public const CSS_URL = 'https://cdn.jsdelivr.net/gh/ray-di/Ray.Di@' . self::VERSION . '/docs/bindings/bindings.css';
    public const JS_URL = 'https://cdn.jsdelivr.net/gh/ray-di/Ray.Di@' . self::VERSION . '/docs/bindings/bindings.js';

    /**
     * The reusable core: the markdown as a <pre> data island and the mount
     * point the viewer decorates. Embed this in a page that references
     * {@see CSS_URL} and {@see JS_URL}.
     */
    public function fragment(string $bindingsMarkdown): string
    {
        $md = htmlspecialchars($bindingsMarkdown, ENT_QUOTES, 'UTF-8');

        return "<pre id=\"src\">{$md}</pre>\n<div id=\"view\"></div>";
    }

    /**
     * A standalone page around {@see fragment()}, linking the shared viewer
     * assets from the CDN — used by bin/bindings-html.
     *
     * The optional $message renders as a subtitle, e.g. the context the
     * bindings were composed in ("prod-app").
     */
    public function page(string $bindingsMarkdown, string $message = ''): string
    {
        $fragment = $this->fragment($bindingsMarkdown);
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
            <link rel="stylesheet" href="{$css}">
            </head>
            <body>
            <div class="wrap">
            <header>
            <h1>Ray.Di bindings</h1>{$sub}
            </header>
            {$fragment}
            </div>
            <script src="{$js}"></script>
            </body>
            </html>

            HTML;
    }
}
