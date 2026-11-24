<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Operator\Internal\ViewModels\SaysWhereTheWorkStands;
use UnitEnum;

/**
 * A screen following one job at a time through {@see FollowsAJob}, answering for that job alone.
 *
 * The job is followed for {@see TheOnlyJob::Followed}, unless the screen
 * names what else it is followed for in {@see theJobItFollows()}. The screen
 * answers three hooks that name no job; a hook asked about any other job
 * raises {@see NotAJobItFollows} rather than answer about the wrong one.
 *
 * @template TReport of SaysWhereTheWorkStands
 */
trait FollowsOneJobAtATime
{
    /** @use FollowsAJob<TReport> */
    use FollowsAJob;

    /** @return TReport */
    abstract protected function whatTheJobCameTo(Stack $stack, Session $session, Job $job): SaysWhereTheWorkStands;

    /** @return TReport */
    abstract protected function whileNoJobIsFollowed(): SaysWhereTheWorkStands;

    /** @return TReport */
    abstract protected function withNoSessionHeld(): SaysWhereTheWorkStands;

    /** The one job while it runs, for an act on it by its name; none once it has ended. */
    protected function theRunningJob(): ?Job
    {
        foreach ($this->running() as $for => $followed) {
            if ($for === $this->theJobItFollows()->name) {
                return $followed->job;
            }
        }

        return null;
    }

    /** Whether the one job's last report, kept until the screen asks again, said it runs. */
    protected function lastSaidItRuns(): bool
    {
        foreach ($this->became as $for => $report) {
            if ($for === $this->theJobItFollows()->name) {
                return $report->stands->isWorking();
            }
        }

        return false;
    }

    /** What the one job is followed for. */
    protected function theJobItFollows(): UnitEnum
    {
        return TheOnlyJob::Followed;
    }

    /** @return TReport */
    protected function whatBecameOfIt(Stack $stack, Session $session, Job $job, UnitEnum $for): SaysWhereTheWorkStands
    {
        return $for === $this->theJobItFollows() ? $this->whatTheJobCameTo($stack, $session, $job) : throw NotAJobItFollows::named($for);
    }

    /** @return TReport */
    protected function whileNothingIsFollowed(UnitEnum $for): SaysWhereTheWorkStands
    {
        return $for === $this->theJobItFollows() ? $this->whileNoJobIsFollowed() : throw NotAJobItFollows::named($for);
    }

    /** @return TReport */
    protected function withoutASession(UnitEnum $for): SaysWhereTheWorkStands
    {
        return $for === $this->theJobItFollows() ? $this->withNoSessionHeld() : throw NotAJobItFollows::named($for);
    }
}
