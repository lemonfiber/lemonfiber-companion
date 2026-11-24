<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Closure;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\Presenters\AgoAsShown;
use Modules\Operator\Internal\Presenters\HowTheRunReads;
use Modules\Operator\Internal\ViewModels\TheRunTurnedOutToBe;
use Native\Mobile\Edge\NativeComponent;

/**
 * Following a run of checks that disturb to its end: the job it answered with, where it has got to, and when its report came back.
 *
 * One trait for running one check and running every check or a family's,
 * because following the job is the same work either way; what each screen
 * draws from the report it came back with is its own, in
 * {@see self::cameBackWith()}. The run is followed through
 * {@see FollowsOneJobAtATime}.
 *
 * @phpstan-require-extends NativeComponent
 */
trait FollowsARunOfChecks
{
    /** @use FollowsOneJobAtATime<TheRunTurnedOutToBe> */
    use FollowsOneJobAtATime;

    /** Whenthe run's report came back, in whole seconds since the epoch, or nought before one has. */
    public int $ranAt = 0;

    /** Where the run has got to: not asked, still running, or what it came to. */
    public function howItIsGoing(): TheRunTurnedOutToBe
    {
        return $this->whatBecameOfTheJob();
    }

    /** How long ago the run's report came back, or nothing before one has. */
    public function ranAgo(): AgoAsShown
    {
        if ($this->ranAt === 0) {
            return AgoAsShown::saysNothing();
        }

        $ran = Instant::atEpochSeconds($this->ranAt);
        $now = $this->listening->clock->now();

        return AgoAsShown::since($ran, $now);
    }

    /** Take in the report the run came back with. */
    abstract protected function cameBackWith(Report $report): void;

    protected function whatTheJobCameTo(Stack $stack, Session $session, Job $job): TheRunTurnedOutToBe
    {
        return $this->shown($this->runs->whatBecameOf($stack, $session, $job), $stack);
    }

    protected function whileNoJobIsFollowed(): TheRunTurnedOutToBe
    {
        return new HowTheRunReads()->notAsked();
    }

    protected function withNoSessionHeld(): TheRunTurnedOutToBe
    {
        return new HowTheRunReads()->signedOut();
    }

    /**
     * Start a run, letting go of the one followed before.
     *
     * @param Closure(Stack, Session): WhatBecameOfTheWork<Report> $asking
     */
    private function startTheRun(Closure $asking): void
    {
        $this->forget();
        $this->reported($this->put($asking));
    }

    /**
     * Ask the stack, with the session this device holds for it, and follow what it answers.
     *
     * @param Closure(Stack, Session): WhatBecameOfTheWork<Report> $asking
     */
    private function put(Closure $asking): TheRunTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheRunTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: $this->withNoSessionHeld(...),
        );
    }

    /**
     * @param WhatBecameOfTheWork<Report> $became
     */
    private function shown(WhatBecameOfTheWork $became, Stack $stack): TheRunTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheRunTurnedOutToBe {
                $this->follow($job);

                return new HowTheRunReads()->running();
            },
            answered: function (Report $report): TheRunTurnedOutToBe {
                $this->ranAt = $this->listening->clock->now()->epochSeconds();
                $this->cameBackWith($report);

                return new HowTheRunReads()->answered();
            },
            ended: static fn(?WhyTheWorkEnded $why): TheRunTurnedOutToBe => new HowTheRunReads()->ended($why),
            refused: static fn(ARefusalInItsWords $why): TheRunTurnedOutToBe => new HowTheRunReads()->refused($why),
            met: function (Obstacle $why) use ($stack): TheRunTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheRunReads()->met($why);
            },
        );
    }
}
