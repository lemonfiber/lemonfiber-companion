<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowTheVerbIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatElseIsRunning;
use Modules\Kernel\Api\WhatFormsThereAre;
use Modules\Kernel\Api\WhatIsRunning;
use Modules\Kernel\Api\WhatItTakesAway;

/**
 * A stack where a test says what is running, and which remembers what it was
 * told to do about it.
 *
 * {@see AStackThatStalled}'s sibling for starting and stopping, and it
 * remembers the same half a screen cannot assert about itself — which stack was
 * asked — for the same reason: a screen holding two stacks and stopping a
 * service on the wrong machine is two machines mistaken for each other, exactly
 * where it costs the most.
 *
 * **What it was told is kept, in order.** That is this fake's own half, and it
 * is what a test of a confirmation stands on: the thing worth proving about a
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

    /** @var list<AgreedTo> Everything it was asked to rehearse, in the order it was asked. */
    private array $rehearsed = [];

    /** @var list<Job> Every handle it was asked after, in the order it was asked. */
    private array $followed = [];

    /**
    /** How many times it was asked for its forms, which are counted apart from {@see askings()}. */
    private int $formsAskings = 0;

    /**
     * @param Closure(): WhatIsRunning $answer
     * @param Closure(): Underway      $acting
     * @param ?HowTheVerbIsGoing       $becoming what asking after a verb answers, where a test said; still running where none did
     * @param ?WhatFormsThereAre       $declares what asking for its forms answers, where a test said; none where nobody did, or the obstacle {@see met()} meets
     */
    private function __construct(
        private readonly Closure $answer,
        private readonly Closure $acting,
        private readonly ?HowTheVerbIsGoing $becoming = null,
        private readonly ?WhatFormsThereAre $declares = null,
    ) {}

    /** A stack running these, which takes what it is told. */
    public static function with(Daemons $daemons): self
    {
        return new self(
            static fn(): WhatIsRunning => WhatIsRunning::these($daemons, WhatElseIsRunning::nothing()),
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
        );
    }

    /**
     * A machine running the listing, and other things nobody declared.
     *
     * A constructor of its own rather than a default on {@see with()}, because
     * *the machine reported nothing undeclared* and *nobody said* are different
     * answers and a default would spell them the same way.
     */
    public static function alsoRunning(Daemons $daemons, WhatElseIsRunning $elsewhere): self
    {
        return new self(
            static fn(): WhatIsRunning => WhatIsRunning::these($daemons, $elsewhere),
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

    /** A stack the operator could not reach, for the reason given, every way. */
    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): WhatIsRunning => WhatIsRunning::met($why),
            static fn(): Underway => Underway::met($why),
            HowTheVerbIsGoing::met($why),
            WhatFormsThereAre::met($why),
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
            static fn(): WhatIsRunning => WhatIsRunning::these($daemons, WhatElseIsRunning::nothing()),
            static fn(): Underway => Underway::met($why),
        );
    }

    /**
     * A stack that answers once and then stops.
     *
     * The window a screen holding a question across frames actually meets: the
     * listing was read, somebody tapped, and by the next frame the machine is
     * not answering. What the screen then holds is a question about a reading
     * it no longer has — which is where the guards on anything derived from
     * both of them earn their place, and which no fake answering the same thing
     * forever can produce.
     */
    public static function thenMeeting(Daemons $first, Obstacle $why): self
    {
        // A flag rather than a count, because the rule is *once, then that
        // answer from then on* and a counter says it in two places that have to
        // agree — the increment and the comparison — while carrying values
        // (three, four, five) that nothing here ever reads.
        $hasAnswered = false;

        return new self(
            static function () use ($first, $why, &$hasAnswered): WhatIsRunning {
                if ($hasAnswered) {
                    return WhatIsRunning::met($why);
                }

                $hasAnswered = true;

                return WhatIsRunning::these($first, WhatElseIsRunning::nothing());
            },
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
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
        // The same flag as {@see thenMeeting()}, for the same reason.
        $hasAnswered = false;

        return new self(
            static function () use ($first, $andThen, &$hasAnswered): WhatIsRunning {
                $listing = $hasAnswered ? $andThen : $first;
                $hasAnswered = true;

                return WhatIsRunning::these($listing, WhatElseIsRunning::nothing());
            },
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
        );
    }

    /**
     * The same stack, which answers asking after a verb with `$became`.
     *
     * A wither rather than a constructor per outcome, because what a verb
     * came to is independent of what the stack lists and every listing above
     * wants to be followable.
     */
    public function whichCameTo(HowTheVerbIsGoing $became): self
    {
        return new self($this->answer, $this->acting, $became, $this->declares);
    }

    /**
     * The same stack, which declares these forms.
     *
     * A wither for {@see whichCameTo()}'s reason: what a stack declares is
     * independent of what it lists running.
     */
    public function declaring(Forms $forms): self
    {
        return new self($this->answer, $this->acting, $this->becoming, WhatFormsThereAre::these($forms));
    }

    /** The same stack, whose forms could not be listed, for the reason given. */
    public function whoseFormsMeet(Obstacle $why): self
    {
        return new self($this->answer, $this->acting, $this->becoming, WhatFormsThereAre::met($why));
    }

    /** How many times it was asked for its forms. */
    public function formsAskings(): int
    {
        return $this->formsAskings;
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
        $this->askings++;

        return ($this->answer)();
    }

    public function told(Stack $stack, Session $session, AgreedTo $agreed): Underway
    {
        $this->remember($stack, $session);
        $this->askings++;
        $this->told[] = $agreed;

        return ($this->acting)();
    }

    /**
     * Rehearsals are answered as the verb would be, and counted apart from it.
     *
     * Not among {@see askings()}, which count readings and verbs: a rehearsal
     * changes nothing, and a test about a verb being sent once must not count
     * the question asked before the yes.
     */
    public function rehearsed(Stack $stack, Session $session, AgreedTo $agreed): Underway
    {
        $this->remember($stack, $session);
        $this->rehearsed[] = $agreed;

        return ($this->acting)();
    }

    /**
     * Everything it was asked to rehearse, in order.
     *
     * @return list<AgreedTo>
     */
    public function whatItWasAskedToRehearse(): array
    {
        return $this->rehearsed;
    }

    public function formsOn(Stack $stack, Session $session): WhatFormsThereAre
    {
        $this->remember($stack, $session);
        $this->formsAskings++;

        return $this->declares ?? WhatFormsThereAre::these(Forms::none());
    }

    /**
     * Not counted among {@see askings()}, which count readings of the listing
     * and verbs: following is its own question, asked on its own cadence.
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheVerbIsGoing
    {
        $this->followed[] = $job;

        return $this->becoming ?? HowTheVerbIsGoing::stillRunning();
    }

    /**
     * Every handle it was asked after, in order.
     *
     * @return list<Job>
     */
    public function followed(): array
    {
        return $this->followed;
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

        // The session is read and the value dropped, which is
        // {@see AStackThatStalled::stoppedOn()}'s argument: a fake holding one
        // is the one place a fixture could teach the habit of keeping a secret
        // past its use, and reading it is what proves the port was handed one.
        $this->carried = $session->forTheHeader() !== '';
    }
}
