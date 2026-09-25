<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Tracing;
use Modules\Kernel\Api\WhatToFollow;
use Modules\Kernel\Api\WhatWasFoundOfTheTrace;
use Modules\Kernel\Api\WhereItGotTo;

/**
 * A stack where a test says where an item got to, and which remembers what it was asked to follow.
 *
 * {@see AStackThatExplainsItsWords}' sibling one endpoint along.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatTraces implements Tracing
{
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** What it was last asked to follow, or empty where it never was. */
    private string $followed = '';

    /** How many times, which is how a screen that polls is caught. */
    private int $askings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /** @param Closure(): WhatWasFoundOfTheTrace $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack answering with this trace. */
    public static function with(WhereItGotTo $trace): self
    {
        return new self(static fn(): WhatWasFoundOfTheTrace => WhatWasFoundOfTheTrace::found($trace));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatWasFoundOfTheTrace => WhatWasFoundOfTheTrace::met($why));
    }

    /** The stack it was last asked about, or nothing where it never was. */
    public function askedAbout(): ?Stack
    {
        return $this->askedAbout;
    }

    /** What it was last asked to follow. */
    public function followed(): string
    {
        return $this->followed;
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

    public function tracedOn(Stack $stack, Session $session, WhatToFollow $following): WhatWasFoundOfTheTrace
    {
        $this->askedAbout = $stack;
        $this->followed = $following->term();
        $this->askings++;

        // The session is read and the value dropped, for
        // {@see AStackThatKeepsARecord}'s reason.
        $this->carried = $session->forTheHeader() !== '';

        return ($this->answer)();
    }
}
