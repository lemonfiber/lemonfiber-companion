<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Stalled;
use Modules\Kernel\Api\Stalling;
use Modules\Kernel\Api\WhatIsStuck;

/**
 * A stack where a test says what has stopped, and which remembers being asked.
 *
 * {@see AHouseholdThatAsked}'s sibling one endpoint along, and it remembers the
 * same half a screen cannot assert about itself: *which* stack was asked. A
 * screen holding two stacks and listing one machine's stalled downloads under
 * the other's name is two machines mistaken for each other where an operator
 * would act on it — they would go and restart a service on a machine where
 * nothing was wrong.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatStalled implements Stalling
{
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** How many times, which is how a screen that polls is caught. */
    private int $askings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /** @param Closure(): WhatIsStuck $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack where these have stopped. */
    public static function with(Stalled $stalled): self
    {
        return new self(static fn(): WhatIsStuck => WhatIsStuck::these($stalled));
    }

    /**
     * A stack where nothing has stopped, which is the answer worth its own name.
     *
     * {@see AHouseholdThatAsked::wantingNothing()}'s argument, and stronger
     * here: *nothing is stuck* and *the stack could not be asked* are the two
     * answers this screen exists to keep apart, and a test reading
     * `met(...)` beside `with(Stalled::nothing())` should not have to work out
     * which of them is the reassuring one.
     */
    public static function withNothingStuck(): self
    {
        return new self(static fn(): WhatIsStuck => WhatIsStuck::these(Stalled::nothing()));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatIsStuck => WhatIsStuck::met($why));
    }

    /** The stack it was last asked about, or nothing where it never was. */
    public function askedAbout(): ?Stack
    {
        return $this->askedAbout;
    }

    /** How many times it was asked, which catches a screen asking twice a frame. */
    public function askings(): int
    {
        return $this->askings;
    }

    /** Whether it was handed a session with something in it. */
    public function wasGivenASession(): bool
    {
        return $this->carried;
    }

    public function stoppedOn(Stack $stack, Session $session): WhatIsStuck
    {
        $this->askedAbout = $stack;
        $this->askings++;

        // The session is read and the value dropped, which is
        // `AHouseholdThatAsked`'s argument: a fake holding one is the one place
        // a fixture could teach the habit of keeping a secret past its use,
        // and reading it is what proves the port was handed one at all.
        $this->carried = $session->forTheHeader() !== '';

        return ($this->answer)();
    }
}
