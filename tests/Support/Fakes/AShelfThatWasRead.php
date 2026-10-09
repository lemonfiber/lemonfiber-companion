<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PartWays;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Shelf;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Watching;
use Modules\Kernel\Api\WhatTheTitleIs;
use Modules\Kernel\Api\WhatTheyArePartWayThrough;
use Modules\Kernel\Api\WhatTheyMayWatch;
use Modules\Kernel\Api\Whose;
use Override;

/**
 * A stack that says what a member may watch, without one being there.
 *
 * `G1`'s shape: an implementation of the port with its one method written out,
 * so a screen test can put a member in front of a shelf without a stack. A
 * mock would drift the day the port changed; this stops compiling.
 *
 * **It carries the shelf it was given and builds none.** That is the promise
 * the real adapter makes and the one worth holding a fake to: a fake that
 * assembled a shelf from parts would let a screen pass against a library the
 * core would never have listed.
 */
final class AShelfThatWasRead implements Watching
{
    /** @param list<string> $titlesAskedFor */
    private function __construct(
        private readonly Shelf $shelf,
        private readonly Sentences $said,
        private readonly ?Obstacle $why,
        private readonly bool $outOfReach,
        private ?Stack $asked = null,
        private int $askings = 0,
        private int $askingsForTheDefaults = 0,
        private ?WhatTheTitleIs $title = null,
        private array $titlesAskedFor = [],
        private ?PartWays $partWay = null,
        private int $partWayAskings = 0,
        private ?Obstacle $partWayWhy = null,
    ) {}

    /** A member with things on their shelf. */
    public static function holding(Shelf $shelf): self
    {
        return new self(shelf: $shelf, said: Sentences::none(), why: null, outOfReach: false);
    }

    /** A member whose shelf is empty, which is an answer rather than a silence. */
    public static function holdingNothing(): self
    {
        return new self(shelf: Shelf::none(), said: Sentences::none(), why: null, outOfReach: false);
    }

    /** A library the core could not reach, and what it said about that. */
    public static function outOfReach(Sentences $said): self
    {
        return new self(shelf: Shelf::none(), said: $said, why: null, outOfReach: true);
    }

    /** A stack that would not say, and what stood in the way. */
    public static function met(Obstacle $why): self
    {
        return new self(shelf: Shelf::none(), said: Sentences::none(), why: $why, outOfReach: false);
    }

    /** The same shelf, answering a title with this. */
    public function answeringTheTitle(WhatTheTitleIs $title): self
    {
        $this->title = $title;

        return $this;
    }

    /** The same shelf, answering what the member was part-way through with this. */
    public function answeringPartWay(PartWays $partWay): self
    {
        $this->partWay = $partWay;

        return $this;
    }

    /** The same shelf, refusing only what the member was part-way through, for this reason. */
    public function refusingPartWay(Obstacle $why): self
    {
        $this->partWayWhy = $why;

        return $this;
    }

    /** How many times what the member was part-way through was asked. */
    public function partWayAskings(): int
    {
        return $this->partWayAskings;
    }

    /**
     * Each title asked for, by its id, in order.
     *
     * @return list<string>
     */
    public function titlesAskedFor(): array
    {
        return $this->titlesAskedFor;
    }

    /** Which stack was asked, for a test that cares that the right one was. */
    public function askedAbout(): ?Stack
    {
        return $this->asked;
    }

    /**
     * How many times it has been asked.
     *
     * A screen reads a machine once per frame, and the only way to hold it to
     * that is to count.
     */
    public function askings(): int
    {
        return $this->askings;
    }

    /**
     * How many of those askings were for the household's defaults.
     *
     * The preview reads nobody's shelf, and the only way to hold it to that is
     * to count which of the two it asked.
     */
    public function askingsForTheDefaults(): int
    {
        return $this->askingsForTheDefaults;
    }

    #[Override]
    public function theShelfOf(Stack $stack, Session $session, Whose $whose): WhatTheyMayWatch
    {
        return $this->answer($stack);
    }

    /** The title it was told to answer with, or the shelf's obstacle, or absent. */
    #[Override]
    public function theTitle(Stack $stack, Session $session, Whose $whose, HoldingId $title): WhatTheTitleIs
    {
        $this->asked = $stack;
        $this->titlesAskedFor[] = $title->named();

        if ($this->why instanceof Obstacle) {
            return WhatTheTitleIs::refused($this->why);
        }

        return $this->title ?? WhatTheTitleIs::absent();
    }

    /** What it was told the member was part-way through, or the shelf's obstacle, or nothing. */
    #[Override]
    public function partWayThrough(Stack $stack, Session $session, Whose $whose): WhatTheyArePartWayThrough
    {
        $this->partWayAskings++;
        $why = $this->partWayWhy ?? $this->why;

        if ($why instanceof Obstacle) {
            return WhatTheyArePartWayThrough::refused($why);
        }

        return WhatTheyArePartWayThrough::told($this->partWay ?? PartWays::of());
    }

    #[Override]
    public function theDefaultShelf(Stack $stack, Session $session): WhatTheyMayWatch
    {
        $this->askingsForTheDefaults++;

        return $this->answer($stack);
    }

    /** The shelf it was given, whichever of the two was asked. */
    private function answer(Stack $stack): WhatTheyMayWatch
    {
        $this->asked = $stack;
        $this->askings++;

        if ($this->why instanceof Obstacle) {
            return WhatTheyMayWatch::refused($this->why);
        }

        return $this->outOfReach
            ? WhatTheyMayWatch::outOfReach($this->said)
            : WhatTheyMayWatch::told($this->shelf);
    }
}
