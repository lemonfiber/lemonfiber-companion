<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\History;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheRecord;
use Modules\Kernel\Api\WhatWasRecorded;

/**
 * A stack where a test says what its record holds, and which remembers being asked.
 *
 * {@see AStackThatHosts}'s sibling one endpoint along, and it remembers the
 * same half a screen cannot assert about itself: *which* stack was asked. A
 * screen showing one machine's record under another's name has an operator
 * go and undo something on the machine it was never done to.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatKeepsARecord implements History
{
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** How many times, which is how a screen that polls is caught. */
    private int $askings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /** @param Closure(): WhatWasRecorded $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack whose record holds this. */
    public static function with(TheRecord $record): self
    {
        return new self(static fn(): WhatWasRecorded => WhatWasRecorded::record($record));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatWasRecorded => WhatWasRecorded::met($why));
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

    public function recordedOn(Stack $stack, Session $session): WhatWasRecorded
    {
        $this->askedAbout = $stack;
        $this->askings++;

        // The session is read and the value dropped, for {@see AStackThatHosts}'
        // reason: a fake holding one is the one place a fixture could teach the
        // habit of keeping a secret past its use.
        $this->carried = $session->forTheHeader() !== '';

        return ($this->answer)();
    }
}
