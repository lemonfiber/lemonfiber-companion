<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;
use function trim;

/**
 * One run of changes, named by the stamp the stack's record keeps it under.
 *
 * A run is what an operator agreed to — a seed, a reconfigure — and the stack
 * stamps every change of one run with the same moment. So the stamp is how a
 * run is asked for, and how the record's rows are told to belong to it.
 *
 * **The stamp is the stack's text, carried as it wrote it.** Seconds since the
 * epoch in decimal, and `0` where its clock would not answer. `0` is still a
 * stamp: every change the stack could not date carries it, and a run asked
 * for by it is every one of those the same operation made, which is what the
 * record lists under it.
 */
final readonly class ARun
{
    /** The stamp the stack writes where its clock would not answer. */
    private const string UNDATED = '0';

    private function __construct(private string $stamp) {}

    /** A run by the stamp a screen was handed; a blank one is refused. */
    public static function stamped(string $stamp): self
    {
        $said = trim($stamp);

        if ($said === '') {
            throw UndoSaysNothing::about('at');
        }

        return new self($said);
    }

    /**
     * The run a change was made in, by the moment the record gave it.
     *
     * The record reads the stamp into a moment, or into the clock not saying;
     * this is the same text read back, so a run asked for from a row is the
     * run the stack stamped that row with.
     */
    public static function madeAt(WhenItWasMade $when): self
    {
        return $when->either(
            at: static fn(Instant $at): self => new self(sprintf('%d', $at->epochSeconds())),
            unreadable: static fn(): self => new self(self::UNDATED),
        );
    }

    /** The stamp, for asking with and for building a path. */
    public function stamp(): string
    {
        return $this->stamp;
    }

    /** Whether a change the record holds was made in this run. */
    public function holds(Change $change): bool
    {
        return self::madeAt($change->when())->stamp === $this->stamp;
    }
}
