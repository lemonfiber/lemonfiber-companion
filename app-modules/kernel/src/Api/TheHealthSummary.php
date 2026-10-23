<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * The one line every surface says about a stack, and what it expands to.
 *
 * Computed by the core, once, for every surface at the same time: the word, how
 * many things want attention counted by cause rather than by symptom, the worst
 * of them named, and every affected item worst first. The app holds what came
 * back and computes none of it, which is what makes the line the same here as
 * on the machine.
 *
 * What stopped moving in the queue travels with it, because the core sends it
 * in the same event: a summary saying downloads have stopped, heard without the
 * rows saying which kind of stopped, would be a line with nothing under it.
 *
 * Iterating it walks the affected items in the order the core put them in.
 *
 * @implements IteratorAggregate<int, AnAffectedItem>
 */
final readonly class TheHealthSummary implements IteratorAggregate
{
    /** @param list<AnAffectedItem> $affected */
    private function __construct(
        private HowItStands $standing,
        private int $wantingAttention,
        private string $worst,
        private WhatStoppedMoving $stopped,
        private array $affected,
        private DownloadsMoving $moving,
    ) {}

    /**
     * The summary as the core sent it.
     *
     * `$worst` is empty where the core named nothing, which it does where nothing
     * is wrong.
     */
    public static function of(
        HowItStands $standing,
        int $wantingAttention,
        string $worst,
        WhatStoppedMoving $stopped,
        AnAffectedItem ...$affected,
    ): self {
        if ($wantingAttention < 0) {
            throw SummaryCountsBelowNothing::wanting($wantingAttention);
        }

        return new self($standing, $wantingAttention, $worst, $stopped, array_values($affected), DownloadsMoving::these());
    }

    /**
     * The same summary, with the downloads the stack said are moving.
     *
     * Added as the read adds it rather than as another argument: a summary the
     * phone kept says nothing about them, because a download moves on long
     * before a kept summary is opened again.
     */
    public function moving(DownloadsMoving $moving): self
    {
        return new self($this->standing, $this->wantingAttention, $this->worst, $this->stopped, $this->affected, $moving);
    }

    public function standing(): HowItStands
    {
        return $this->standing;
    }

    public function wantingAttention(): int
    {
        return $this->wantingAttention;
    }

    public function worst(): string
    {
        return $this->worst;
    }

    /** What the queue says has stopped moving, worst first. */
    public function stopped(): WhatStoppedMoving
    {
        return $this->stopped;
    }

    /** The downloads the stack said are moving, or none where it said none or could not say. */
    public function downloadsMoving(): DownloadsMoving
    {
        return $this->moving;
    }

    /** @return Traversable<int, AnAffectedItem> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->affected);
    }
}
