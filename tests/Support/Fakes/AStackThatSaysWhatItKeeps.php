<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Storing;
use Modules\Kernel\Api\WhatThisMachineKeeps;
use Modules\Kernel\Api\WhatWasFoundKept;

/**
 * A stack where a test says what it keeps, and which remembers being asked.
 *
 * {@see AStackThatRationsItsLine}'s sibling one endpoint along.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatSaysWhatItKeeps implements Storing
{
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** How many times, which is how a screen that polls is caught. */
    private int $askings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /** @param Closure(): WhatWasFoundKept $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack that keeps this. */
    public static function with(WhatThisMachineKeeps $keeps): self
    {
        return new self(static fn(): WhatWasFoundKept => WhatWasFoundKept::kept($keeps));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatWasFoundKept => WhatWasFoundKept::met($why));
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

    public function storedOn(Stack $stack, Session $session): WhatWasFoundKept
    {
        $this->askedAbout = $stack;
        $this->askings++;

        // The session is read and the value dropped, for
        // {@see AStackThatKeepsARecord}'s reason.
        $this->carried = $session->forTheHeader() !== '';

        return ($this->answer)();
    }
}
