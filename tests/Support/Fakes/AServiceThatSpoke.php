<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Saying;
use Modules\Kernel\Api\Scrollback;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasSaid;

/**
 * A service that said what a test says, and which remembers being asked.
 *
 * {@see AStackThatStalled}'s sibling one endpoint along. It remembers more than
 * the others do, because this port takes two arguments the others do not and
 * both are clauses of `N2-R10`: **which service** was asked about and **how
 * many lines** were asked for. A screen that read one service's logs under
 * another's heading, or that asked for a bound it did not then state, would be
 * wrong in a way no assertion about the lines themselves could catch.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AServiceThatSpoke implements Saying
{
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** The service it was last asked about, or nothing where it never was. */
    private ?ServiceId $askedFor = null;

    /** The bound it was last given, or nothing where it never was. */
    private ?HowManyLines $askedWith = null;

    /** How many times, which is how a screen that polls is caught. */
    private int $askings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /** @param Closure(): WhatWasSaid $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A service whose tail reads like this. */
    public static function saying(Scrollback $scrollback): self
    {
        return new self(static fn(): WhatWasSaid => WhatWasSaid::this($scrollback));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatWasSaid => WhatWasSaid::met($why));
    }

    /** The stack it was last asked about, or nothing where it never was. */
    public function askedAbout(): ?Stack
    {
        return $this->askedAbout;
    }

    /** The service it was last asked about, or nothing where it never was. */
    public function askedFor(): ?ServiceId
    {
        return $this->askedFor;
    }

    /** The bound it was last given, or nothing where it never was. */
    public function askedWith(): ?HowManyLines
    {
        return $this->askedWith;
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

    public function saidBy(Stack $stack, Session $session, ServiceId $service, HowManyLines $lines): WhatWasSaid
    {
        $this->askedAbout = $stack;
        $this->askedFor = $service;
        $this->askedWith = $lines;
        $this->askings++;

        // The session is read and the value dropped, which is
        // `AHouseholdThatAsked`'s argument: a fake holding one is the one place
        // a fixture could teach the habit `N4-R5` exists to prevent, and
        // reading it is what proves the port was handed one at all.
        $this->carried = $session->forTheHeader() !== '';

        return ($this->answer)();
    }
}
