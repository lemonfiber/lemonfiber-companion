<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Provenance;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheOriginsWere;
use Modules\Kernel\Api\WhereTheServicesComeFrom;

/**
 * A stack where a test says where its services come from, and which remembers being asked.
 *
 * {@see AStackThatKeepsARecord}'s sibling one endpoint along, remembering the
 * same half a screen cannot assert about itself: *which* stack was asked. One
 * machine's licences shown under another's name are an answer somebody acts on
 * for the wrong machine.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatNamesItsOrigins implements Provenance
{
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** How many times, which is how a screen that polls is caught. */
    private int $askings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /** @param Closure(): WhatTheOriginsWere $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack whose services come from here. */
    public static function with(WhereTheServicesComeFrom $origins): self
    {
        return new self(static fn(): WhatTheOriginsWere => WhatTheOriginsWere::origins($origins));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatTheOriginsWere => WhatTheOriginsWere::met($why));
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

    public function declaredOn(Stack $stack, Session $session): WhatTheOriginsWere
    {
        $this->askedAbout = $stack;
        $this->askings++;

        // The session is read and the value dropped, for
        // {@see AStackThatKeepsARecord}'s reason.
        $this->carried = $session->forTheHeader() !== '';

        return ($this->answer)();
    }
}
