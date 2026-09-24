<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Rationing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheLineWasFound;

/**
 * A stack where a test says how its line is shared, and which remembers being asked.
 *
 * {@see AStackThatSaysWhatItTells}' sibling one endpoint along, remembering
 * *which* stack was asked: one machine's line shown under another's
 * name is an answer about whose evening is being held back.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatRationsItsLine implements Rationing
{
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** How many times, which is how a screen that polls is caught. */
    private int $askings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /** @param Closure(): WhatTheLineWasFound $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack whose line is shared like this. */
    public static function with(HowTheLineIsShared $shared): self
    {
        return new self(static fn(): WhatTheLineWasFound => WhatTheLineWasFound::shared($shared));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatTheLineWasFound => WhatTheLineWasFound::met($why));
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

    public function rationedOn(Stack $stack, Session $session): WhatTheLineWasFound
    {
        $this->askedAbout = $stack;
        $this->askings++;

        // The session is read and the value dropped, for
        // {@see AStackThatKeepsARecord}'s reason.
        $this->carried = $session->forTheHeader() !== '';

        return ($this->answer)();
    }
}
