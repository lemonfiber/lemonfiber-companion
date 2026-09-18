<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Asking;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatCameBack;

/**
 * A stack that answers what a test told it to, and remembers being asked.
 *
 * The stand-in every screen showing health will get, so that no test of a
 * report needs a machine to report on. What it remembers is the half a screen
 * cannot assert about itself: *which* stack was asked. A screen holding two
 * stacks and showing one machine's findings under the other's name is two
 * machines mistaken for each other where an operator would act on it — and a
 * fake that forgot the stack would make that green.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatWasAsked implements Asking
{
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** How many times, which is how a screen that polls is caught. */
    private int $askings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /** @param Closure(): WhatCameBack $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack that answers with this report. */
    public static function saying(Report $report): self
    {
        return new self(static fn(): WhatCameBack => WhatCameBack::report($report));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatCameBack => WhatCameBack::met($why));
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

    public function about(Stack $stack, Session $session): WhatCameBack
    {
        $this->askedAbout = $stack;
        $this->askings++;

        // The session is read and the value dropped. A fake holding one is the
        // one place a fixture could teach the habit of keeping a secret past its
        // use, and the real adapter uses it only to build a connection — but
        // reading it is what proves the port was handed one at all.
        $this->carried = $session->forTheHeader() !== '';

        return ($this->answer)();
    }
}
