<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Closure;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Operator\Internal\Presenters\AgoAsShown;
use Modules\Operator\Internal\Presenters\HowTheRunReads;
use Modules\Operator\Internal\ViewModels\TheRunTurnedOutToBe;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * Following a run of checks that disturb to its end: the handle it answered with, where it has got to, and when its report came back.
 *
 * One trait for running one check and running every check or a family's,
 * because following the job is the same work either way; what each screen
 * draws from the report it came back with is its own, in
 * {@see self::cameBackWith()}.
 *
 * @phpstan-require-extends NativeComponent
 */
trait FollowsARunOfChecks
{
    /** The handle of the run being followed, where there is one. */
    public ?string $following = null;

    /** When the run's report came back, in whole seconds since the epoch, or nought before one has. */
    public int $ranAt = 0;

    /** Where the run has got to, once this frame has asked. */
    public ?TheRunTurnedOutToBe $going = null;

    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->howItIsGoing()->isWorking) {
            $this->going = null;
        }
    }

    public function awaitsAnOutcome(): bool
    {
        return $this->howItIsGoing()->isWorking;
    }

    /** Where the run has got to: not asked, still running, or what it came to. */
    public function howItIsGoing(): TheRunTurnedOutToBe
    {
        $following = $this->following;

        return $this->going ??= $following === null
            ? new HowTheRunReads()->notAsked()
            : $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->runs->whatBecameOf($stack, $session, Job::named($following)));
    }

    /** How long ago the run's report came back, or nothing before one has. */
    public function ranAgo(): AgoAsShown
    {
        if ($this->ranAt === 0) {
            return AgoAsShown::unsaid();
        }

        $ran = Instant::atEpochSeconds($this->ranAt);
        $now = $this->listening->clock->now();

        return AgoAsShown::from(HowLongAgo::since($ran, $now), $ran, $now);
    }

    /** Take in the report the run came back with. */
    abstract protected function cameBackWith(Report $report): void;

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
            notHeld: static fn(): TheRunTurnedOutToBe => new HowTheRunReads()->signedOut(),
        );
    }

    /**
     * @param WhatBecameOfTheWork<Report> $became
     */
    private function shown(WhatBecameOfTheWork $became, Stack $stack): TheRunTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheRunTurnedOutToBe {
                $this->following = $job->shown();

                return new HowTheRunReads()->running();
            },
            answered: function (Report $report): TheRunTurnedOutToBe {
                $this->following = null;
                $this->ranAt = $this->listening->clock->now()->epochSeconds();
                $this->cameBackWith($report);

                return new HowTheRunReads()->answered();
            },
            ended: function (): TheRunTurnedOutToBe {
                $this->following = null;

                return new HowTheRunReads()->ended();
            },
            refused: function (ARefusalInItsWords $why): TheRunTurnedOutToBe {
                $this->following = null;

                return new HowTheRunReads()->refused($why);
            },
            met: function (Obstacle $why) use ($stack): TheRunTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheRunReads()->met($why);
            },
        );
    }
}
