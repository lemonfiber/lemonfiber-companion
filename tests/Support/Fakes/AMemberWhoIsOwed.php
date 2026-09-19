<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Owing;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheyAreOwed;
use Modules\Kernel\Api\WhatTheyAsked;
use Override;

/**
 * A stack that says what a member is owed, without one being there.
 *
 * `G1`'s shape: an implementation of the port with its methods written out, so a
 * screen test can put a member in front of their own reading without a stack. A
 * mock would drift the day the port changed; this stops compiling.
 *
 * **Both questions, answered the same way.** What a member is owed and what they
 * asked for are two calls on one port, and an obstacle is an obstacle to both: a
 * stack that would not say has not said either half.
 *
 * **It carries the sentences it was given and composes none.** That is the
 * promise the real adapter makes and the one worth holding a fake to: a fake
 * that assembled a wording from parts would let a screen pass against sentences
 * the core would never have written.
 */
final class AMemberWhoIsOwed implements Owing
{
    private function __construct(
        private readonly Sentences $said,
        private readonly ?Obstacle $why,
        private readonly Requested $wanted,
        private ?Stack $asked = null,
        private int $askings = 0,
        private int $listings = 0,
    ) {}

    /** A member the stack has something to tell. */
    public static function owed(Sentences $said): self
    {
        return new self(said: $said, why: null, wanted: Requested::none());
    }

    /** A member the stack has nothing to tell, which is an answer rather than a silence. */
    public static function owedNothing(): self
    {
        return new self(said: Sentences::none(), why: null, wanted: Requested::none());
    }

    /** A stack that would not say, and what stood in the way. */
    public static function met(Obstacle $why): self
    {
        return new self(said: Sentences::none(), why: $why, wanted: Requested::none());
    }

    /**
     * A member with things they have asked for, and sentences about the asking.
     *
     * Both together because a screen reads both, and a fake that could only be
     * given one would let a screen pass while dropping the other.
     */
    public static function owedAndAsking(Sentences $said, Requested $wanted): self
    {
        return new self(said: $said, why: null, wanted: $wanted);
    }

    /**
     * A member who has asked for things and is owed no sentences about asking.
     *
     * An ordinary state rather than a contrived one: a stack with nothing to say
     * about somebody's allowance still has their requests to report.
     */
    public static function asking(Requested $wanted): self
    {
        return new self(said: Sentences::none(), why: null, wanted: $wanted);
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
     * that is to count. A fake that answered without counting would let a
     * screen ask on every accessor and look identical from the outside.
     */
    public function askings(): int
    {
        return $this->askings;
    }

    /**
     * How many times it has been asked what they asked for.
     *
     * Counted apart from {@see askings()} because they are two questions, and one
     * counter for both would let a screen ask one of them twice and the other not
     * at all while the total looked right.
     */
    public function listings(): int
    {
        return $this->listings;
    }

    #[Override]
    public function toHandOver(Stack $stack, Session $session): WhatTheyAreOwed
    {
        $this->asked = $stack;
        $this->askings++;

        return $this->why instanceof Obstacle
            ? WhatTheyAreOwed::refused($this->why)
            : WhatTheyAreOwed::told($this->said);
    }

    #[Override]
    public function whatTheyAsked(Stack $stack, Session $session): WhatTheyAsked
    {
        $this->asked = $stack;
        $this->listings++;

        return $this->why instanceof Obstacle
            ? WhatTheyAsked::refused($this->why)
            : WhatTheyAsked::told($this->wanted);
    }
}
