<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatIsRunning;
use Modules\Kernel\Api\WhatItTakesAway;

/**
 * A stack where a test says what is running, and which remembers what it was
 * told to do about it.
 *
 * {@see AStackThatStalled}'s sibling for `N2-R7`, and it remembers the same
 * half a screen cannot assert about itself — which stack was asked — for the
 * same reason: a screen holding two stacks and stopping a service on the wrong
 * machine is `N1-R11` broken exactly where it costs the most.
 *
 * **What it was told is kept, in order.** That is this fake's own half, and it
 * is what a test of `N2-R8` stands on: the thing worth proving about a
 * confirmation is that nothing reached the port before the operator agreed, and
 * only the port can say whether it did.
 *
 * Not `readonly`: what was asked and what was told are written as they happen.
 */
final class AStackThatSupervises implements Supervising
{
    /** The name a test reads back where it did not choose one. */
    public const string THE_JOB = 'a-job-a-test-can-name';

    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** How many times, which is how a screen that polls is caught. */
    private int $askings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /** @var list<AgreedTo> Everything it was told to do, in the order it was told. */
    private array $told = [];

    /**
     * @param Closure(): WhatIsRunning $answer
     * @param Closure(): Underway      $acting
     */
    private function __construct(private readonly Closure $answer, private readonly Closure $acting) {}

    /** A stack running these, which takes what it is told. */
    public static function with(Daemons $daemons): self
    {
        return new self(
            static fn(): WhatIsRunning => WhatIsRunning::these($daemons),
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
        );
    }

    /**
     * A stack running nothing at all, which is the answer worth its own name.
     *
     * {@see AStackThatStalled::withNothingStuck()}'s argument, and it lands the
     * other way up here: *nothing is running* is the state an operator opens
     * the app to change, and a test reading it beside `met(...)` should not
     * have to work out which of the two is the one to act on.
     */
    public static function withNothingRunning(): self
    {
        return self::with(Daemons::none(Disturbances::of(
            starting: WhatItTakesAway::atMost(180),
            stopping: WhatItTakesAway::atMost(10),
            restarting: WhatItTakesAway::atMost(180),
        )));
    }

    /** A stack the operator could not reach, for the reason given, either way. */
    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): WhatIsRunning => WhatIsRunning::met($why),
            static fn(): Underway => Underway::met($why),
        );
    }

    /**
     * A stack that says what it is running and refuses the verb.
     *
     * The two halves of the port can disagree, and on one obstacle they
     * routinely do: a session that ended between the frame and the tap is a
     * stack that listed its services and then refused to act on one.
     *
     * {@see met()} cannot stand in for this. It refuses both halves, so the
     * screen never gets a listing, never has a row to agree to, and never sends
     * a verb — which leaves the arm that folds a refused verb unreached by any
     * test that thinks it is testing exactly that.
     */
    public static function withButRefusing(Daemons $daemons, Obstacle $why): self
    {
        return new self(
            static fn(): WhatIsRunning => WhatIsRunning::these($daemons),
            static fn(): Underway => Underway::met($why),
        );
    }

    /**
     * A stack whose listing changes between one frame and the next.
     *
     * The first reading, then the second, and the second from then on. A screen
     * holds a question across frames while the machine underneath it keeps
     * moving, so what was agreed to and what is now listed can disagree — which
     * a fake answering the same thing forever cannot produce, and which is
     * where more than one guard on these screens earns its place.
     */
    public static function thenRunning(Daemons $first, Daemons $andThen): self
    {
        $reading = 0;

        return new self(
            static function () use ($first, $andThen, &$reading): WhatIsRunning {
                $reading++;

                return WhatIsRunning::these($reading === 1 ? $first : $andThen);
            },
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
        );
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

    /**
     * Everything it was told to do, in order.
     *
     * @return list<AgreedTo>
     */
    public function whatItWasToldToDo(): array
    {
        return $this->told;
    }

    public function running(Stack $stack, Session $session): WhatIsRunning
    {
        $this->remember($stack, $session);

        return ($this->answer)();
    }

    public function told(Stack $stack, Session $session, AgreedTo $agreed): Underway
    {
        $this->remember($stack, $session);
        $this->told[] = $agreed;

        return ($this->acting)();
    }

    /**
     * What both halves of the port record about being reached.
     *
     * One place, so that a test asserting *nothing reached the stack* is
     * asserting the same thing about a read as about a verb.
     */
    private function remember(Stack $stack, Session $session): void
    {
        $this->askedAbout = $stack;
        $this->askings++;

        // The session is read and the value dropped, which is
        // {@see AStackThatStalled::stoppedOn()}'s argument: a fake holding one
        // is the one place a fixture could teach the habit `N4-R5` exists to
        // prevent, and reading it is what proves the port was handed one.
        $this->carried = $session->forTheHeader() !== '';
    }
}
