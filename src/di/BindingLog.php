<?php

declare(strict_types=1);

namespace Ray\Di;

use Stringable;

use function array_diff_key;
use function array_fill_keys;
use function array_map;
use function array_push;
use function implode;

/**
 * Composition-time record of every write to the dependency container
 *
 * Module composition merges silently: bind() overwrites (last wins), while
 * install() and constructor chaining merge with `+=` (existing wins, incoming
 * silently discarded). This log records each write as a BindingEvent and
 * tracks per-index provenance, so "who bound X, who was overwritten, who was
 * discarded, in which module" is observable — the precedence contract made
 * auditable.
 *
 * Composition-time only: the log observes writes and never influences
 * resolution, and it is excluded from Container serialization — a revived
 * container starts with an empty log and attributes later writes 'unknown'.
 * Pointcuts and MultiBindings are NOT logged in v1.
 */
final class BindingLog implements Stringable
{
    /** @var list<BindingEvent> */
    private array $events = [];

    /** @var array<string, string> Index => module FQCN owning the current binding */
    private array $sources = [];

    /**
     * Record a direct container write: a first bind or a replace
     *
     * @param string  $index      Dependency index '{interface}-{name}'
     * @param string  $dependency String form of the dependency now bound
     * @param ?string $previous   String form of the overwritten dependency, null on a first bind
     * @param string  $source     Module FQCN performing the write
     */
    public function register(string $index, string $dependency, ?string $previous, string $source): void
    {
        if ($previous === null) {
            $this->events[] = new BindingEvent(BindingEvent::BIND, $index, $dependency, $source);
            $this->sources[$index] = $source;

            return;
        }

        $this->events[] = new BindingEvent(
            BindingEvent::REPLACE,
            $index,
            $dependency,
            $source,
            $previous,
            // 'unknown' when provenance predates this log (e.g. after unserialize())
            $this->sources[$index] ?? 'unknown'
        );
        $this->sources[$index] = $source;
    }

    /**
     * Record a container merge: adopt the incoming history, log each collision
     *
     * The incoming log's events are appended as-is (the writes did happen, in
     * that module), every colliding index becomes a keep event naming both the
     * surviving and the discarded side, and provenance is adopted only for the
     * indexes actually taken over — colliding indexes keep their owner.
     *
     * Merging the same log instance twice (e.g. installing one module object
     * two times) replays its events verbatim: the duplicate lines are a
     * faithful trace of the duplicate merge, not distinct writes.
     *
     * @param list<string>          $collidingIndexes      Indexes present on both sides (existing wins)
     * @param array<string, string> $keptDependencies      Colliding index => surviving dependency string
     * @param array<string, string> $discardedDependencies Colliding index => discarded incoming dependency string
     */
    public function merge(self $other, array $collidingIndexes, array $keptDependencies, array $discardedDependencies): void
    {
        // in-place append of event object refs: O(adopted), no list re-copy
        array_push($this->events, ...$other->events);
        foreach ($collidingIndexes as $index) {
            $this->events[] = new BindingEvent(
                BindingEvent::KEEP,
                $index,
                $keptDependencies[$index],
                $this->sources[$index] ?? 'unknown',
                $discardedDependencies[$index],
                $other->sources[$index] ?? 'unknown'
            );
        }

        $this->sources += array_diff_key($other->sources, array_fill_keys($collidingIndexes, true));
    }

    /**
     * Record an index move (rename), transferring provenance to the new index
     *
     * The moved binding still belongs to the module that bound it, not to the
     * module performing the rename.
     */
    public function move(string $from, string $to): void
    {
        if (isset($this->sources[$from])) {
            $this->sources[$to] = $this->sources[$from];
            unset($this->sources[$from]);
        }

        $this->events[] = new BindingEvent(BindingEvent::MOVE, $to, '', $this->sources[$to] ?? 'unknown', null, null, $from);
    }

    /**
     * @return list<BindingEvent>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @return array<string, string> Index => module FQCN owning the current binding
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    public function getSource(string $index): ?string
    {
        return $this->sources[$index] ?? null;
    }

    public function __toString(): string
    {
        return implode("\n", array_map(static fn (BindingEvent $event): string => (string) $event, $this->events));
    }
}
