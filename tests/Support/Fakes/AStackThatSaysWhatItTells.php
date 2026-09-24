<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Telling;
use Modules\Kernel\Api\WhatTheAlertsWere;
use Modules\Kernel\Api\WhatTheOperatorIsTold;

/**
 * A stack where a test says what its operator is told about, and which remembers being asked.
 *
 * {@see AStackThatSaysWhatLeavesIt}' sibling one endpoint along, remembering
 * *which* stack was asked: one machine's alert setting shown under another's
 * name is an answer about being woken by the wrong machine.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatSaysWhatItTells implements Telling
{
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** How many times, which is how a screen that polls is caught. */
    private int $askings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /** @param Closure(): WhatTheAlertsWere $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack whose operator is told about this. */
    public static function with(WhatTheOperatorIsTold $told): self
    {
        return new self(static fn(): WhatTheAlertsWere => WhatTheAlertsWere::told($told));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatTheAlertsWere => WhatTheAlertsWere::met($why));
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

    public function toldAbout(Stack $stack, Session $session): WhatTheAlertsWere
    {
        $this->askedAbout = $stack;
        $this->askings++;

        // The session is read and the value dropped, for
        // {@see AStackThatKeepsARecord}'s reason.
        $this->carried = $session->forTheHeader() !== '';

        return ($this->answer)();
    }
}
