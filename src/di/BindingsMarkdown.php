<?php

declare(strict_types=1);

namespace Ray\Di;

use Ray\Bindings\BindingsMarkdown as BindingsMarkdownRenderer;

/** @deprecated Use Ray\Bindings\BindingsMarkdown. */
final class BindingsMarkdown
{
    private readonly BindingsMarkdownRenderer $renderer;

    public function __construct()
    {
        $this->renderer = new BindingsMarkdownRenderer();
    }

    public function __invoke(Container $container, string $classDir): void
    {
        ($this->renderer)($container, $classDir);
    }

    public function render(Container $container): string
    {
        return $this->renderer->render($container);
    }
}
