<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Hosting;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatKeepsRunning;
use Modules\Kernel\Api\WhatRunsUnattended;

/**
 * A stack where a test says what is kept running, and which remembers being asked.
 *
 * {@see AStackThatStalled}'s sibling one endpoint along, and it remembers the
 * same half a screen cannot assert about itself: *which* stack was asked. A
 * screen holding two stacks and showing one machine's launch agents under the
 * other's name is two machines mistaken for each other where an operator would
 * act on it — they would go and set something up on a machine that already had
 * it.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatHosts implements Hosting
{
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** How many times, which is how a screen that polls is caught. */
    private int $askings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /** @param Closure(): WhatKeepsRunning $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A machine keeping these running. */
    public static function with(WhatRunsUnattended $running): self
    {
        return new self(static fn(): WhatKeepsRunning => WhatKeepsRunning::keeps($running));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatKeepsRunning => WhatKeepsRunning::met($why));
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

    public function keptRunningOn(Stack $stack, Session $session): WhatKeepsRunning
    {
        $this->askedAbout = $stack;
        $this->askings++;

        // The session is read and the value dropped, which is
        // {@see AStackThatStalled}'s argument: a fake holding one is the one
        // place a fixture could teach the habit of keeping a secret past its
        // use, and reading it is what proves the port was handed one at all.
        $this->carried = $session->forTheHeader() !== '';

        return ($this->answer)();
    }
}
