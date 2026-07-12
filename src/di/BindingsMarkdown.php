<?php

declare(strict_types=1);

namespace Ray\Di;

use Throwable;

use function file_put_contents;

/**
 * Emit the composed container as bindings.md next to the generated classes
 *
 * Two sections: the provenance log (who bound what, who was discarded) and the
 * resolved bindings. Written at composition time — before aspect weaving, so
 * class names are the originals and no proxy graph is serialized. Best-effort:
 * a diagnostics artifact must never break container construction.
 */
final class BindingsMarkdown
{
    public function __invoke(Container $container, string $classDir): void
    {
        try {
            $markdown = "# Ray.Di bindings\n\n"
                . "## Provenance\n\n" . (string) $container->log . "\n\n"
                . "## Bindings\n\n" . (new ModuleString())($container, $container->getPointcuts()) . "\n";
            file_put_contents($classDir . '/bindings.md', $markdown);
        } catch (Throwable) { // @codeCoverageIgnoreStart
            // best-effort: never break construction for a diagnostics file
        } // @codeCoverageIgnoreEnd
    }
}
