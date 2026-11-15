<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * How the queue heals itself: whether healing acts, the strikes and grace it acts after, every unhealthy item of the latest pass, what it could not reach, and every run.
 *
 * Iterating it walks the unhealthy items in the order the stack sent them.
 *
 * @implements IteratorAggregate<int, AWedgedItem>
 */
final readonly class HowTheQueueHeals implements IteratorAggregate
{
    /** @param list<AWedgedItem> $items */
    private function __construct(
        private WhetherHealingActs $healing,
        private int $strikesNeeded,
        private HowLong $grace,
        private WhenItSays $classifiedAt,
        private string $mismatch,
        private bool $incomplete,
        private bool $rehearsed,
        private array $items,
        private WhatHealingCouldNotReach $unreachable,
        private TheHealingRuns $runs,
    ) {}

    /**
     * Healing switched this way, acting after this many strikes and leaving a new download alone this long, as classified at this moment or never.
     *
     * `$mismatch` is where a download client files a curator's downloads outside the curator's folder, in the stack's words, or empty.
     */
    public static function switched(WhetherHealingActs $healing, int $strikesNeeded, HowLong $grace, WhenItSays $classifiedAt, string $mismatch): self
    {
        return new self($healing, $strikesNeeded, $grace, $classifiedAt, $mismatch, incomplete: false, rehearsed: false, items: [], unreachable: WhatHealingCouldNotReach::these(), runs: TheHealingRuns::these());
    }

    /** The same answer, whose list may be short because something could not be read. */
    public function mayBeShort(): self
    {
        return new self($this->healing, $this->strikesNeeded, $this->grace, $this->classifiedAt, $this->mismatch, incomplete: true, rehearsed: $this->rehearsed, items: $this->items, unreachable: $this->unreachable, runs: $this->runs);
    }

    /** The same answer, given by a run that only said what it would do. */
    public function rehearsed(): self
    {
        return new self($this->healing, $this->strikesNeeded, $this->grace, $this->classifiedAt, $this->mismatch, incomplete: $this->incomplete, rehearsed: true, items: $this->items, unreachable: $this->unreachable, runs: $this->runs);
    }

    /** The same answer, with these unhealthy items, in the order the stack sent them. */
    public function finding(AWedgedItem ...$items): self
    {
        return new self($this->healing, $this->strikesNeeded, $this->grace, $this->classifiedAt, $this->mismatch, $this->incomplete, $this->rehearsed, array_values($items), $this->unreachable, $this->runs);
    }

    /** The same answer, with these services it could not reach or verify. */
    public function notReaching(AServiceHealingCouldNotReach ...$unreachable): self
    {
        return new self($this->healing, $this->strikesNeeded, $this->grace, $this->classifiedAt, $this->mismatch, $this->incomplete, $this->rehearsed, $this->items, WhatHealingCouldNotReach::these(...$unreachable), $this->runs);
    }

    /** The same answer, with these runs, newest first. */
    public function after(AHealingRun ...$runs): self
    {
        return new self($this->healing, $this->strikesNeeded, $this->grace, $this->classifiedAt, $this->mismatch, $this->incomplete, $this->rehearsed, $this->items, $this->unreachable, TheHealingRuns::these(...$runs));
    }

    /** Whether healing acts, or only classifies. */
    public function healing(): WhetherHealingActs
    {
        return $this->healing;
    }

    /** How many passes in a row flag an item for one reason before it is acted on. */
    public function strikesNeeded(): int
    {
        return $this->strikesNeeded;
    }

    /** How long a newly added download is left alone. */
    public function grace(): HowLong
    {
        return $this->grace;
    }

    /** When the latest pass classified the queue, or that none ever has. */
    public function classifiedAt(): WhenItSays
    {
        return $this->classifiedAt;
    }

    /** Where a download client files a curator's downloads outside the curator's folder, empty where none does. */
    public function mismatch(): string
    {
        return $this->mismatch;
    }

    /** Whether the list may be short, because something could not be read. */
    public function isIncomplete(): bool
    {
        return $this->incomplete;
    }

    /** Whether this answer is a run that only said what it would do. */
    public function isARehearsal(): bool
    {
        return $this->rehearsed;
    }

    /** Every curator or download client the latest pass could not reach or verify. */
    public function unreachable(): WhatHealingCouldNotReach
    {
        return $this->unreachable;
    }

    /** Every run, newest first. */
    public function runs(): TheHealingRuns
    {
        return $this->runs;
    }

    /** @return Traversable<int, AWedgedItem> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
