<?php

declare(strict_types=1);

namespace Ray\Di;

use Stringable;

use function sprintf;

/**
 * A single composition-time write to the dependency container
 *
 * Four kinds of write exist: a first bind, a replace (bind() over an existing
 * entry, last write wins), a keep (merge collision where the existing entry
 * wins and the incoming one is silently discarded by `+=`), and a move
 * (rename to a new index). Each event names the module responsible for the
 * surviving state and, for replace/keep, the losing side — the information a
 * silent merge would otherwise erase.
 */
final class BindingEvent implements Stringable
{
    public const BIND = 'bind';
    public const REPLACE = 'replace';
    public const KEEP = 'keep';
    public const MOVE = 'move';

    /**
     * @param string  $type            One of the four type constants
     * @param string  $index           Dependency index '{interface}-{name}'
     * @param string  $dependency      String form of the winning/current dependency ('' for move)
     * @param string  $source          Module FQCN owning the current state
     * @param ?string $discarded       String form of the losing dependency (replace/keep only)
     * @param ?string $discardedSource Module FQCN that owned the losing dependency (replace/keep only)
     * @param ?string $movedFrom       Old index the binding left (move only)
     * @psalm-param self::BIND|self::REPLACE|self::KEEP|self::MOVE $type
     * @phpstan-param self::BIND|self::REPLACE|self::KEEP|self::MOVE $type
     */
    public function __construct(
        public readonly string $type,
        public readonly string $index,
        public readonly string $dependency,
        public readonly string $source,
        public readonly ?string $discarded = null,
        public readonly ?string $discardedSource = null,
        public readonly ?string $movedFrom = null
    ) {
    }

    public function __toString(): string
    {
        $body = match ($this->type) {
            self::BIND => sprintf('%s => %s @%s', $this->index, $this->dependency, $this->source),
            self::REPLACE => sprintf('%s => %s @%s (replaced %s @%s)', $this->index, $this->dependency, $this->source, $this->discarded ?? '', $this->discardedSource ?? ''),
            self::KEEP => sprintf('%s => %s @%s (discarded %s @%s)', $this->index, $this->dependency, $this->source, $this->discarded ?? '', $this->discardedSource ?? ''),
            self::MOVE => sprintf('%s => %s @%s', $this->movedFrom ?? '', $this->index, $this->source),
        };

        return sprintf('%-7s', $this->type) . ' ' . $body;
    }
}
