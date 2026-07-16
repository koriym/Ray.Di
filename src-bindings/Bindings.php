<?php

declare(strict_types=1);

namespace Ray\Bindings;

use Ray\Di\BindingsMarkdown;
use Ray\Di\Container;
use Ray\Di\Exception\BindingsNotCollected;
use Ray\Di\ModuleVisitorInterface;

/** An immutable-at-render-time snapshot of a module's composed bindings. */
final class Bindings implements ModuleVisitorInterface
{
    private ?string $markdown = null;

    public function visit(Container $container): void
    {
        $this->markdown = (new BindingsMarkdown())->render($container);
    }

    public function toMarkdown(): string
    {
        return $this->getMarkdown();
    }

    public function toHtml(string $composerLock = '', string $message = '', string $vendorDir = ''): string
    {
        return (new BindingsHtml())->page($this->getMarkdown(), $composerLock, $message, $vendorDir);
    }

    private function getMarkdown(): string
    {
        if ($this->markdown === null) {
            throw new BindingsNotCollected('Collect bindings with AbstractModule::accept() before rendering.');
        }

        return $this->markdown;
    }
}
