<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\HowTheOfferIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Mending;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;

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

    /**
     * @param Closure(): Underway           $started
     * @param Closure(): HowTheOfferIsGoing $became
     */
    private function __construct(private readonly Closure $started, private readonly Closure $became) {}

    /** A stack that takes the question on and answers with this listing. */
    public static function offering(Offer $offer): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(): HowTheOfferIsGoing => HowTheOfferIsGoing::offering($offer),
        );
    }

    /** A stack still working out what it would do. */
    public static function stillWorkingItOut(): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(): HowTheOfferIsGoing => HowTheOfferIsGoing::stillRunning(),
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
        );
    }

    /** A stack that could not be asked at all, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): Underway => Underway::met($why),
            static fn(): HowTheOfferIsGoing => HowTheOfferIsGoing::met($why),
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
