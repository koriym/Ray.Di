<?php

declare(strict_types=1);

namespace Ray\Di;

use Throwable;

use function count;
use function file_put_contents;
use function implode;
use function ksort;
use function sprintf;

/**
 * Emit the composed container as bindings.md next to the generated classes
 *
 * A summary line, the resolved bindings, the modules that composed them, and
 * the provenance log (who bound what, who was discarded). Written at
 * composition time — before aspect weaving, so class names are the originals
 * and no proxy graph is serialized. Best-effort: a diagnostics artifact must
 * never break container construction.
 */
final class BindingsMarkdown
{
    public function __invoke(Container $container, string $classDir): void
    {
        try {
            file_put_contents($classDir . '/bindings.md', $this->render($container));
        } catch (Throwable) { // @codeCoverageIgnoreStart
            // best-effort: never break construction for a diagnostics file
        } // @codeCoverageIgnoreEnd
    }

    private function render(Container $container): string
    {
        $log = $container->log;
        $replaced = 0;
        $discarded = 0;
        foreach ($log->getEvents() as $event) {
            if ($event->type === BindingEvent::REPLACE) {
                $replaced++;
            }

            if ($event->type === BindingEvent::KEEP) {
                $discarded++;
            }
        }

        /** @var array<string, int> $moduleCounts */
        $moduleCounts = [];
        foreach ($log->getSources() as $module) {
            $moduleCounts[$module] = ($moduleCounts[$module] ?? 0) + 1;
        }

        ksort($moduleCounts);
        $moduleLines = [];
        foreach ($moduleCounts as $module => $count) {
            $moduleLines[] = sprintf('- %s (%d)', $module, $count);
        }

        return sprintf(
            "# Ray.Di bindings\n\n%d bindings · %d modules · %d replaced · %d discarded\n\n"
                . "## Bindings\n\n%s\n\n## Modules\n\n%s\n\n## Provenance\n\n%s\n",
            count($container->getContainer()),
            count($moduleCounts),
            $replaced,
            $discarded,
            (new ModuleString())($container, $container->getPointcuts()),
            implode("\n", $moduleLines),
            $log
        );
    }
}
