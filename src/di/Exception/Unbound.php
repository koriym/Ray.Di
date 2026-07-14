<?php

declare(strict_types=1);

namespace Ray\Di\Exception;

use Exception;
use LogicException;

use function array_pop;
use function array_reverse;
use function implode;
use function sprintf;

/**
 * Exception thrown when a binding is not found
 *
 * Message format: '{type}-{name}' in file:line ($var)
 *
 * Examples:
 * - "'FooInterface-' in file.php:10 ($foo)" - FooInterface with no name
 * - "'FooInterface-db' in file.php:10 ($foo)" - FooInterface with name 'db'
 * - "'-db' in file.php:10 ($foo)" - no type with name 'db'
 */
class Unbound extends LogicException implements ExceptionInterface
{
    public function __toString(): string
    {
        $messages = [sprintf("- %s\n", $this->getMessage())];
        $e = $this->getPrevious();
        if (! $e instanceof Exception) {
            return $this->getMainMessage($this);
        }

        if ($e instanceof self) {
            return $this->buildMessage($e, $messages) . "\n" . $e->getTraceAsString();
        }

        return parent::__toString();
    }

    /** @param array<int, string> $msg */
    private function buildMessage(self $e, array $msg): string
    {
        $lastE = $e;
        while ($e instanceof self) {
            $msg[] = sprintf("- %s\n", $e->getMessage());
            $lastE = $e;
            $e = $e->getPrevious();
        }

        array_pop($msg);
        $msg = array_reverse($msg);

        return $this->getMainMessage($lastE) . implode('', $msg);
    }

    /** @psalm-pure */
    private function getMainMessage(self $e): string
    {
        return sprintf(
            "exception '%s' with message '%s'\n",
            $e::class,
            $e->getMessage()
        );
    }
}
