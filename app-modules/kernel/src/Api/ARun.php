<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function filter_var;
use function is_int;
use function sprintf;
use function trim;

/**
 * One run of changes, named by the stamp the stack's record keeps it under.
 *
 * A run is what an operator agreed to — a seed, a reconfigure — and the stack
 * stamps every change of one run with the same moment. So the stamp is how a
 * run is asked for, and how the record's rows are told to belong to it.
 *
 * **The stamp is whole seconds since the epoch**, and `0` where the stack's
 * clock would not answer. `0` is still a stamp: every change the stack could
 * not date carries it, and a run asked for by it is every one of those the
 * same operation made, which is what the record lists under it.
 */
final readonly class ARun implements WhatAScreenIsAbout
{
    /** @param int<0, max> $seconds */
    private function __construct(private int $seconds) {}

    /** A run by the stamp a screen was handed; anything but whole seconds since the epoch is refused. */
    public static function stamped(string $stamp): self
    {
        $seconds = filter_var($stamp, FILTER_VALIDATE_INT, ['options' => ['min_range' => Instant::UNDATED]]);

        if (trim($stamp) === '') {
            throw UndoSaysNothing::about('at');
        }

        if (! is_int($seconds)) {
            throw UndoSaysNothing::notAStamp($stamp);
        }

        return new self($seconds);
    }

    /**
     * The run a change was made in, by the moment the record gave it.
     *
     * The record reads the stamp into a moment, or into the clock not saying;
     * this is the same moment read back, so a run asked for from a row is the
     * run the stack stamped that row with.
     */
    public static function madeAt(WhenItWasMade $when): self
    {
        return $when->either(
            at: static fn(Instant $at): self => new self($at->epochSeconds()),
            unreadable: static fn(): self => new self(Instant::UNDATED),
        );
    }

    /**
     * The stamp as whole seconds since the epoch, for asking with.
     *
     * @return int<0, max>
     */
    public function seconds(): int
    {
        return $this->seconds;
    }

    /** The stamp as text, for building a path and for saying which run. */
    public function stamp(): string
    {
        return sprintf('%d', $this->seconds);
    }

    /** Whether a change the record holds was made in this run. */
    public function holds(Change $change): bool
    {
        return self::madeAt($change->when())->seconds === $this->seconds;
    }

    public function namedInAPath(): string
    {
        return $this->stamp();
    }
}
