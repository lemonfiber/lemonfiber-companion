<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Confirmed;
use Modules\Kernel\Api\HowTheOfferIsGoing;
use Modules\Kernel\Api\HowTheRepairIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Mending;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatWasMended;

/**
 * A stack that would put something right, and remembers being asked.
 *
 * The stand-in every screen offering repairs will get. Two things it remembers
 * that a screen cannot assert about itself: which stack was asked, and *which
 * job it was asked after*. The second is the one that matters here — a screen
 * holding a handle and reading a different one is the shape where an operator
 * is shown a listing belonging to somebody else's run, and no assertion about
 * what appeared on the glass would catch it.
 *
 * The name it hands out is fixed rather than generated, because a fake that
 * invented one would make a test asserting the round trip compare two values it
 * had no way to predict — and the round trip is what this fake is for.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatWouldMend implements Mending
{
    /** What the handle is called, for a test to compare against. */
    public const string THE_JOB = 'job-a-test-can-name';

    /** The stack it was last asked about, or nothing where it never was. */
    private ?Stack $askedAbout = null;

    /** The job it was last asked after, or nothing where it never was. */
    private ?Job $askedAfter = null;

    /** How many times work was started, which catches a screen asking twice. */
    private int $askings = 0;

    /** How many times the handle was read, which catches a poller. */
    private int $readings = 0;

    /** Whether the session it was handed carried anything — for a test to ask. */
    private bool $carried = false;

    /** What the operator agreed to, or nothing where they never did. */
    private ?Confirmed $agreed = null;

    /** How many times an agreement was sent, which must never be twice. */
    private int $agreements = 0;

    /**
     * @param Closure(): Underway            $started
     * @param Closure(): HowTheOfferIsGoing  $became
     * @param Closure(): HowTheRepairIsGoing $did
     */
    private function __construct(
        private readonly Closure $started,
        private readonly Closure $became,
        private readonly Closure $did,
    ) {}

    /** A stack that takes the question on and answers with this listing. */
    public static function offering(Offer $offer): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(): HowTheOfferIsGoing => HowTheOfferIsGoing::offering($offer),
            static fn(): HowTheRepairIsGoing => HowTheRepairIsGoing::done(WhatWasMended::none()),
        );
    }

    /** A stack still working out what it would do. */
    public static function stillWorkingItOut(): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(): HowTheOfferIsGoing => HowTheOfferIsGoing::stillRunning(),
            static fn(): HowTheRepairIsGoing => HowTheRepairIsGoing::stillRunning(),
        );
    }

    /**
     * A stack with no outcome for that job any more.
     *
     * Let go of, or restarted since. The state a screen is most likely to fold
     * into one of the others, which is why it has a constructor of its own here
     * rather than being reachable only through an obstacle.
     */
    public static function thatForgotTheJob(): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(): HowTheOfferIsGoing => HowTheOfferIsGoing::ended(),
            static fn(): HowTheRepairIsGoing => HowTheRepairIsGoing::ended(),
        );
    }

    /** A stack that could not be asked at all, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): Underway => Underway::met($why),
            static fn(): HowTheOfferIsGoing => HowTheOfferIsGoing::met($why),
            static fn(): HowTheRepairIsGoing => HowTheRepairIsGoing::met($why),
        );
    }

    /**
     * A stack that took the question on and then could not be reached.
     *
     * The awkward middle, and a real one: a phone leaves the house between
     * asking and reading. A screen built only against the two clean fakes would
     * hold a handle it can never resolve and have no state for it.
     */
    public static function thatWentAwayAfterwards(Obstacle $why): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(): HowTheOfferIsGoing => HowTheOfferIsGoing::met($why),
            static fn(): HowTheRepairIsGoing => HowTheRepairIsGoing::met($why),
        );
    }

    /**
     * A stack that carries out what it is agreed to, and reports these.
     *
     * The offer half answers a listing of one so a test can walk from *what
     * would you do* to *what did you do* without building two fakes — which is
     * the journey `N2-R4` and `N2-R5` describe between them.
     */
    public static function carryingOut(Offer $offer, WhatWasMended $mended): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(): HowTheOfferIsGoing => HowTheOfferIsGoing::offering($offer),
            static fn(): HowTheRepairIsGoing => HowTheRepairIsGoing::done($mended),
        );
    }

    /** The stack it was last asked about, or nothing where it never was. */
    public function askedAbout(): ?Stack
    {
        return $this->askedAbout;
    }

    /** The job it was last asked after, or nothing where it never was. */
    public function askedAfter(): ?Job
    {
        return $this->askedAfter;
    }

    /** How many times work was started. */
    public function askings(): int
    {
        return $this->askings;
    }

    /** How many times the handle was read. */
    public function readings(): int
    {
        return $this->readings;
    }

    /** Whether it was handed a session with something in it. */
    public function wasGivenASession(): bool
    {
        return $this->carried;
    }

    public function wouldPutRight(Stack $stack, Session $session): Underway
    {
        $this->askedAbout = $stack;
        $this->askings++;
        $this->readSession($session);

        return ($this->started)();
    }

    /** What the operator agreed to, or nothing where they never did. */
    public function agreedTo(): ?Confirmed
    {
        return $this->agreed;
    }

    /**
     * How many agreements were sent.
     *
     * The count that matters most on this port. `N1-R41` refuses to replay an
     * action, and an agreement sent twice is a repair carried out twice — which
     * for a fix that moves a library is not the same as doing it once.
     */
    public function agreements(): int
    {
        return $this->agreements;
    }

    public function agreeTo(Stack $stack, Session $session, Confirmed $confirmed): Underway
    {
        $this->askedAbout = $stack;
        $this->agreed = $confirmed;
        $this->agreements++;
        $this->readSession($session);

        return ($this->started)();
    }

    public function whatWasDoneAbout(Stack $stack, Session $session, Job $job): HowTheRepairIsGoing
    {
        $this->askedAbout = $stack;
        $this->askedAfter = $job;
        $this->readings++;
        $this->readSession($session);

        return ($this->did)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheOfferIsGoing
    {
        $this->askedAbout = $stack;
        $this->askedAfter = $job;
        $this->readings++;
        $this->readSession($session);

        return ($this->became)();
    }

    /**
     * Read the session and keep only whether there was one.
     *
     * `AStackThatWasAsked`'s argument: a fake holding one is the one place a
     * fixture could teach the habit `N4-R5` exists to prevent, and reading it
     * is what proves the port was handed one at all.
     */
    private function readSession(Session $session): void
    {
        $this->carried = $session->forTheHeader() !== '';
    }
}
