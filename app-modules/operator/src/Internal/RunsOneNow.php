<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Closure;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatRunningItNowCameTo;
use Modules\Operator\Internal\Presenters\HowTheRunReads;
use Modules\Operator\Internal\Presenters\HowTheScheduleReads;
use Modules\Operator\Internal\ViewModels\TheRunTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheScheduleAsShown;
use Modules\Operator\Internal\ViewModels\WhatRanNowAsShown;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * Running one run-and-exit service at once from the schedule screen: one yes naming it, the run followed to its end, and what the stack answered.
 *
 * Only a service the screen's reading lists can be asked about. Until the
 * stack answers, the reading stays as it was; after, it is read again.
 *
 * The screen that uses it takes {@see \Modules\Kernel\Api\RunningOnASchedule}
 * as a private `$scheduling`, {@see \Modules\Kernel\Api\SecureStorage} as a
 * private `$storage` and {@see \Modules\Kernel\Api\Clock} as a private `$clock`,
 * and holds its reading as a public `$answered`.
 *
 * @phpstan-require-extends NativeComponent
 */
trait RunsOneNow
{
    /** The id of the service waiting for the yes, while one is. */
    public ?string $toRun = null;

    /** The name of the service asked about or running, as the reading gave it. */
    public string $runNamed = '';

    /** The handle of the run being followed, while there is one. */
    public ?string $runFollowed = null;

    /** Where the run has got to, once this frame has asked. */
    public ?TheRunTurnedOutToBe $runGoing = null;

    /** What the stack answered, once it has. */
    public ?WhatRanNowAsShown $ranNow = null;

    /** Ask for the yes to run this service at once; one the reading does not list, or any while a run is followed, asks nothing. */
    public function askToRun(string $service): void
    {
        if ($this->awaitsAnOutcome()) {
            return;
        }

        foreach ($this->answer()->services as $listed) {
            if ($listed->id === $service) {
                $this->toRun = $listed->id;
                $this->runNamed = $listed->name;
            }
        }
    }

    /** Run the service the yes names; without one, nothing is sent. */
    public function runItNow(): void
    {
        $service = $this->toRun;

        if ($service === null) {
            return;
        }

        $this->toRun = null;
        $this->runGoing = $this->runPut(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->scheduling->runNow($stack, $session, ServiceId::called($service)));
    }

    /** Leave the yes, or what the stack answered, unsent and unkept. */
    public function leaveTheRun(): void
    {
        if ($this->awaitsAnOutcome()) {
            return;
        }

        $this->toRun = null;
        $this->runNamed = '';
        $this->runGoing = null;
        $this->ranNow = null;
    }

    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRunsNow(): void
    {
        if ($this->awaitsAnOutcome()) {
            $this->runGoing = null;
        }
    }

    public function awaitsAnOutcome(): bool
    {
        return $this->howTheRunGoes()->stands->isWorking();
    }

    /** Where the run has got to: not asked, still running, or what it came to. */
    public function howTheRunGoes(): TheRunTurnedOutToBe
    {
        $following = $this->runFollowed;

        return $this->runGoing ??= $following === null
            ? new HowTheRunReads()->notAsked()
            : $this->runPut(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->scheduling->whatBecameOf($stack, $session, Job::named($following)));
    }

    /** The run-and-exit services, as the screen reads them. */
    abstract public function answer(): TheScheduleAsShown;

    /** @param Closure(Stack, Session): WhatBecameOfTheWork<WhatRunningItNowCameTo> $asking */
    private function runPut(Closure $asking): TheRunTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheRunTurnedOutToBe => $this->runShown($asking($stack, $session), $stack),
            notHeld: static fn(): TheRunTurnedOutToBe => new HowTheRunReads()->signedOut(),
        );
    }

    /** @param WhatBecameOfTheWork<WhatRunningItNowCameTo> $became */
    private function runShown(WhatBecameOfTheWork $became, Stack $stack): TheRunTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheRunTurnedOutToBe {
                $this->runFollowed = $job->shown();

                return new HowTheRunReads()->running();
            },
            answered: function (WhatRunningItNowCameTo $came): TheRunTurnedOutToBe {
                $this->runFollowed = null;
                $this->ranNow = new HowTheScheduleReads()->ranNow($came, $this->runNamed, $this->clock->now());
                $this->answered = null;

                return new HowTheRunReads()->answered();
            },
            ended: function (): TheRunTurnedOutToBe {
                $this->runFollowed = null;

                return new HowTheRunReads()->ended();
            },
            refused: function (ARefusalInItsWords $why): TheRunTurnedOutToBe {
                $this->runFollowed = null;

                return new HowTheRunReads()->refused($why);
            },
            met: function (Obstacle $why) use ($stack): TheRunTurnedOutToBe {
                $this->runFollowed = null;
                $this->letGoOfTheSession($why, $stack);

                return new HowTheRunReads()->met($why);
            },
        );
    }
}
