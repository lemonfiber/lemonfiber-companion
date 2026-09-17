<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Decided;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\Wanting;
use Modules\Kernel\Api\WhatWasWanted;

/**
 * A household that wants what a test says, and remembers being asked.
 *
 * The stand-in every screen showing requests will get, so that no test of a
 * request list needs a machine with a house behind it. What it remembers is the
 * half a screen cannot assert about itself: *which* stack was asked. A screen
 * holding two stacks and showing one household's requests under the other's
 * name is `N1-R11` broken where an operator would act on it — they would
 * approve a download onto the wrong machine.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AHouseholdThatAsked implements Wanting
{
    /** What a stack calls the work a decision starts, in this fixture. */
    public const string THE_JOB = 'a-job-this-fixture-never-redeems';
    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** How many times, which is how a screen that polls is caught. */
    private int $askings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /**
     * Every decision it was told about, in order.
     *
     * @var list<Decided>
     */
    private array $decided = [];

    /**
     * @param Closure(): WhatWasWanted $answer
     * @param Closure(): Underway      $acting what a decision comes away with
     */
    private function __construct(
        private readonly Closure $answer,
        private readonly Closure $acting,
    ) {}

    /** A household that has asked for these. */
    public static function wanting(Requested $wanted): self
    {
        return new self(static fn(): WhatWasWanted => WhatWasWanted::these($wanted), self::itTookItOn());
    }

    /**
     * A household that has asked for nothing, which is a quiet week.
     *
     * Its own named constructor rather than `wanting(Requested::none())` at
     * each call site, because it is one of the three answers this port gives
     * and the one most likely to be confused with the next: a test reading
     * `met(...)` beside `wanting(Requested::none())` should not have to think
     * about which of them is the empty screen.
     */
    public static function wantingNothing(): self
    {
        return new self(static fn(): WhatWasWanted => WhatWasWanted::these(Requested::none()), self::itTookItOn());
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): WhatWasWanted => WhatWasWanted::met($why),
            static fn(): Underway => Underway::met($why),
        );
    }

    /**
     * A household that answers the reading and meets something on a decision.
     *
     * The narrow case `N3-R13` opens and a reading cannot reach: a stack that
     * refuses the credential the moment somebody taps approve is the same
     * signed-out device as one that refuses a read, and that is a different
     * call.
     */
    public static function wantingButRefusing(Requested $wanted, Obstacle $why): self
    {
        return new self(
            static fn(): WhatWasWanted => WhatWasWanted::these($wanted),
            static fn(): Underway => Underway::met($why),
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
     * Everything it was told an operator decided, in order.
     *
     * @return list<Decided>
     */
    public function whatItWasToldWasDecided(): array
    {
        return $this->decided;
    }

    public function decided(Stack $stack, Session $session, Decided $decided): Underway
    {
        $this->remember($stack, $session);
        $this->decided[] = $decided;

        return ($this->acting)();
    }

    public function askedOf(Stack $stack, Session $session): WhatWasWanted
    {
        $this->remember($stack, $session);

        return ($this->answer)();
    }

    /**
     * What a job it took on comes away as, which no test of a decision is about.
     *
     * A named constructor rather than a literal at each call, so the three
     * readings above say what they are about and not what a handle looks like.
     */
    private static function itTookItOn(): Closure
    {
        return static fn(): Underway => Underway::as(Job::named(self::THE_JOB));
    }

    /**
     * What both halves of the port record about being reached.
     *
     * One place, so a test asserting *nothing reached the stack* is asserting
     * the same thing about a reading as about a decision.
     */
    private function remember(Stack $stack, Session $session): void
    {
        $this->askedAbout = $stack;
        $this->askings++;

        // The session is read and the value dropped, which is
        // `AStackThatWasAsked`'s argument: a fake holding one is the one place
        // a fixture could teach the habit `N4-R5` exists to prevent, and
        // reading it is what proves the port was handed one at all.
        $this->carried = $session->forTheHeader() !== '';
    }
}
