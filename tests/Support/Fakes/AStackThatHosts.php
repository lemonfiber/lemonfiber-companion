<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Hosting;
use Modules\Kernel\Api\HostingAgreed;
use Modules\Kernel\Api\HowTheHandoverWent;
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

    /**
     * Every handing over it was told about, in order, which is how a screen that sends without a yes is caught.
     *
     * @var list<HostingAgreed>
     */
    private array $told = [];

    /**
     * @param Closure(): WhatKeepsRunning $answer
     */
    private function __construct(
        private readonly Closure $answer,
        private readonly HowTheHandoverWent $handingOver,
    ) {}

    /**
     * A machine keeping these running, answering a handing over as a test says.
     *
     * A machine that was not told what a handing over answers says it did not
     * answer, which is a value the port can give rather than a crash in a test
     * that never meant to hand anything over.
     */
    public static function with(WhatRunsUnattended $running, ?HowTheHandoverWent $handingOver = null): self
    {
        return new self(
            static fn(): WhatKeepsRunning => WhatKeepsRunning::keeps($running),
            $handingOver ?? HowTheHandoverWent::met(Obstacle::StackDidNotAnswer),
        );
    }

    /** A stack the operator could not reach, for the reason given, whatever they ask it. */
    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): WhatKeepsRunning => WhatKeepsRunning::met($why),
            HowTheHandoverWent::met($why),
        );
    }

    /**
     * Every handing over it was told about, in order.
     *
     * @return list<HostingAgreed>
     */
    public function told(): array
    {
        return $this->told;
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

    public function handOver(Stack $stack, Session $session, HostingAgreed $agreed): HowTheHandoverWent
    {
        $this->askedAbout = $stack;
        $this->carried = $session->forTheHeader() !== '';
        $this->told[] = $agreed;

        return $this->handingOver;
    }
}
