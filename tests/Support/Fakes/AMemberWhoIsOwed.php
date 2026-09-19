<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Owing;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheyAreOwed;
use Override;

/**
 * A stack that says what a member is owed, without one being there.
 *
 * `G1`'s shape: an implementation of the port with its one method written out,
 * so a screen test can put a member in front of sentences without a stack. A
 * mock would drift the day the port changed; this stops compiling.
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
        private ?Stack $asked = null,
        private int $askings = 0,
    ) {}

    /** A member the stack has something to tell. */
    public static function owed(Sentences $said): self
    {
        return new self(said: $said, why: null);
    }

    /** A member the stack has nothing to tell, which is an answer rather than a silence. */
    public static function owedNothing(): self
    {
        return new self(said: Sentences::none(), why: null);
    }

    /** A stack that would not say, and what stood in the way. */
    public static function met(Obstacle $why): self
    {
        return new self(said: Sentences::none(), why: $why);
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

    #[Override]
    public function toHandOver(Stack $stack, Session $session): WhatTheyAreOwed
    {
        $this->asked = $stack;
        $this->askings++;

        return $this->why instanceof Obstacle
            ? WhatTheyAreOwed::refused($this->why)
            : WhatTheyAreOwed::told($this->said);
    }
}
