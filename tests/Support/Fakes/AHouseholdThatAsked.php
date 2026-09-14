<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Wanting;
use Modules\Kernel\Api\WhatWasWanted;

/**
 * A household that wants what a test says, and remembers being asked.
 *
 * The stand-in every screen showing requests will get, so that no test of a
 * request list needs a machine with a house behind it. What it remembers is the
 * half a screen cannot assert about itself: *which* stack was asked. A screen
 * holding two stacks and showing one household's requests under the other's
 * name is `N1-R11` broken where an operator would act on it — they would
 * approve a download onto the wrong machine.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AHouseholdThatAsked implements Wanting
{
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** How many times, which is how a screen that polls is caught. */
    private int $askings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /** @param Closure(): WhatWasWanted $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A household that has asked for these. */
    public static function wanting(Requested $wanted): self
    {
        return new self(static fn(): WhatWasWanted => WhatWasWanted::these($wanted));
    }

    /**
     * A household that has asked for nothing, which is a quiet week.
     *
     * Its own named constructor rather than `wanting(Requested::none())` at
     * each call site, because it is one of the three answers this port gives
     * and the one most likely to be confused with the next: a test reading
     * `met(...)` beside `wanting(Requested::none())` should not have to think
     * about which of them is the empty screen.
     */
    public static function wantingNothing(): self
    {
        return new self(static fn(): WhatWasWanted => WhatWasWanted::these(Requested::none()));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatWasWanted => WhatWasWanted::met($why));
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

    public function askedOf(Stack $stack, Session $session): WhatWasWanted
    {
        $this->askedAbout = $stack;
        $this->askings++;

        // The session is read and the value dropped, which is
        // `AStackThatWasAsked`'s argument: a fake holding one is the one place
        // a fixture could teach the habit `N4-R5` exists to prevent, and
        // reading it is what proves the port was handed one at all.
        $this->carried = $session->forTheHeader() !== '';

        return ($this->answer)();
    }
}
