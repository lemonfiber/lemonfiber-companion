<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Copying;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheCopies;
use Modules\Kernel\Api\WhatCopiesWereFound;

/**
 * A stack where a test says which copies it holds, and which remembers being asked.
 *
 * {@see AStackThatSaysWhatItKeeps}' sibling, for the second of the two
 * readings on the same screen.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatListsItsCopies implements Copying
{
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** How many times, which is how a screen that polls is caught. */
    private int $askings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /** @param Closure(): WhatCopiesWereFound $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack holding these copies, which may be none. */
    public static function with(TheCopies $copies): self
    {
        return new self(static fn(): WhatCopiesWereFound => WhatCopiesWereFound::copies($copies));
    }

    /** A stack whose copies could not be listed, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatCopiesWereFound => WhatCopiesWereFound::met($why));
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

    public function copiesOn(Stack $stack, Session $session): WhatCopiesWereFound
    {
        $this->askedAbout = $stack;
        $this->askings++;

        // The session is read and the value dropped, for
        // {@see AStackThatKeepsARecord}'s reason.
        $this->carried = $session->forTheHeader() !== '';

        return ($this->answer)();
    }
}
