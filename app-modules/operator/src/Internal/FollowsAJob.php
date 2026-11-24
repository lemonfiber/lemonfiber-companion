<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function array_any;
use function array_diff_key;
use function array_filter;
use function array_intersect_key;
use function array_key_exists;
use function array_map;
use function array_values;

use Modules\Design\Api\ATap;
use Modules\Kernel\Api\AJobFollowed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatAJobSaid;
use Modules\Kernel\Api\WhatTheJobsSaid;
use Modules\Operator\Internal\Presenters\HowWhatAJobSaidReads;
use Modules\Operator\Internal\ViewModels\SaysWhereTheWorkStands;
use Modules\Operator\Internal\ViewModels\WhatAJobSaidAsShown;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;
use UnitEnum;

/**
 * A screen following the jobs the stack named, each to its end.
 *
 * Each job is followed for something, a case of the screen's own enum, or
 * {@see TheOnlyJob::Followed} on a screen that follows one job at a time
 * through {@see FollowsOneJobAtATime}; the hooks are told which, and each
 * has its own report and its own lines, so one job ending never stands in for
 * another. While any job runs, the screen asks after the running ones on
 * {@see HowOftenAScreenLooks::WHILE_WORK_RUNS_MS}, and on the same wakes takes
 * the lines every running job said on the stack's event stream, in one reading
 * on a subscription of its own. What became of a job is the screen's own
 * report, asked through {@see whatBecameOfIt()}; its lines are drawn by
 * `<x-operator::following-a-job>`. Lines no job said stay on the start screen.
 *
 * It reads the screen's own `$hearingTheJobs` and `$storage`, and lets go of a
 * refused session through the screen's {@see \Modules\Connection\Api\LetsGoOfARefusedSession}.
 *
 * @template TReport of SaysWhereTheWorkStands
 *
 * @phpstan-require-extends NativeComponent
 * @phpstan-require-implements AwaitsAnOutcome
 */
trait FollowsAJob
{
    /** @var array<string, AJobFollowed> the jobs followed, by the name of what each is followed for. Not shown and never kept past this screen. */
    public array $followed = [];

    /** @var array<string, UnitEnum> what each job followed is followed for, by its name */
    public array $followedFor = [];

    /** @var array<string, TReport> what became of each job, once this frame has asked */
    public array $became = [];

    /** @var array<string, true> the jobs whose every line is open, rather than only their newest */
    public array $showingAllItSaid = [];

    abstract public function stack(): Stack;

    /** Whether a job followed for any of these, or for anything where none is named, ran when last asked after, answered without asking the stack. */
    public function awaitsAnOutcome(UnitEnum ...$for): bool
    {
        $running = $this->running();

        if ($for === []) {
            return $running !== [];
        }

        return array_any($for, static fn(UnitEnum $one): bool => array_key_exists($one->name, $running));
    }

    /**
     * Take what the running jobs said since the last wake, and ask after each again.
     *
     * Each is asked after here rather than when the screen next draws it, so
     * a job the frame does not draw is still learned to have finished.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        $running = $this->running();

        if ($running === []) {
            return;
        }

        $this->hearWhatTheySaid();
        $this->became = array_diff_key($this->became, $running);

        foreach (array_intersect_key($this->followedFor, $running) as $for) {
            $this->whatBecameOfTheJob($for);
        }
    }

    /** Open every line this job said, where a job is followed for it. */
    public function showAllItSaid(string $for = TheOnlyJob::Followed->name): void
    {
        if (array_key_exists($for, $this->followed)) {
            $this->showingAllItSaid[$for] = true;
        }
    }

    /** Show only this job's newest line. */
    public function showTheNewestItSaid(string $for = TheOnlyJob::Followed->name): void
    {
        unset($this->showingAllItSaid[$for]);
    }

    /** What this job has said so far, as the component draws it. */
    public function whatItSays(UnitEnum $for = TheOnlyJob::Followed): WhatAJobSaidAsShown
    {
        return new HowWhatAJobSaidReads()->of(
            array_key_exists($for->name, $this->followed) ? $this->followed[$for->name]->said : WhatAJobSaid::nothing(),
            array_key_exists($for->name, $this->showingAllItSaid),
            ATap::calling('showAllItSaid', $for->name),
            ATap::calling('showTheNewestItSaid', $for->name),
        );
    }

    /**
     * Follow this job for this from here on, forgetting the one followed for it before and everything that said.
     *
     * Following the job already followed changes nothing, so a stack naming
     * the same job again while it runs keeps what it said.
     */
    protected function follow(Job $job, UnitEnum $for = TheOnlyJob::Followed): void
    {
        if (array_key_exists($for->name, $this->followed) && $this->followed[$for->name]->job->is($job)) {
            return;
        }

        $this->forget($for);
        $this->followed[$for->name] = AJobFollowed::named($job);
        $this->followedFor[$for->name] = $for;
    }

    /** Follow nothing for this. */
    protected function forget(UnitEnum $for = TheOnlyJob::Followed): void
    {
        unset($this->followed[$for->name], $this->followedFor[$for->name], $this->became[$for->name], $this->showingAllItSaid[$for->name]);
        $this->letGoUnlessAnotherRuns();
    }

    /** Whether a job is followed for this, running or finished. */
    protected function follows(UnitEnum $for = TheOnlyJob::Followed): bool
    {
        return array_key_exists($for->name, $this->followed);
    }

    /**
     * What became of the job followed for this, asked once per frame.
     *
     * @return TReport
     */
    protected function whatBecameOfTheJob(UnitEnum $for = TheOnlyJob::Followed): SaysWhereTheWorkStands
    {
        return array_key_exists($for->name, $this->became) ? $this->became[$for->name] : $this->askedAfter($for);
    }

    /**
     * Keep this as what became of the job followed for this, until the screen asks again.
     *
     * @param TReport $report
     * @return TReport
     */
    protected function reported(SaysWhereTheWorkStands $report, UnitEnum $for = TheOnlyJob::Followed): SaysWhereTheWorkStands
    {
        return $this->became[$for->name] = $report;
    }

    /** Ask after the job followed for this again on the next frame, keeping it. */
    protected function askAfterAgain(UnitEnum $for = TheOnlyJob::Followed): void
    {
        unset($this->became[$for->name]);
    }

    /** Let go of the jobs' subscription as the screen stops, keeping what they said. */
    protected function letGoOfWhatElseItHears(): void
    {
        $this->hearingTheJobs->letGo();
    }

    /** @return TReport */
    abstract protected function whatBecameOfIt(Stack $stack, Session $session, Job $job, UnitEnum $for): SaysWhereTheWorkStands;

    /** @return TReport */
    abstract protected function whileNothingIsFollowed(UnitEnum $for): SaysWhereTheWorkStands;

    /** @return TReport */
    abstract protected function withoutASession(UnitEnum $for): SaysWhereTheWorkStands;

    /**
     * Ask the stack what became of the job followed for this, and keep whether it still runs.
     *
     * A job that stopped running has its last lines taken, and the
     * subscription is let go of once no job runs. A report after which the
     * screen follows another job for this, or none, is not kept: it was about
     * work the screen has let go of.
     *
     * @return TReport
     */
    private function askedAfter(UnitEnum $for): SaysWhereTheWorkStands
    {
        if (! $this->follows($for)) {
            $report = $this->whileNothingIsFollowed($for);

            if (array_key_exists($for->name, $this->followed) && ! $report->stands->isWorking()) {
                $this->followed[$for->name] = $this->followed[$for->name]->finished();
            }

            return $this->became[$for->name] = $report;
        }

        $followed = $this->followed[$for->name];
        $stack = $this->stack();
        $report = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): SaysWhereTheWorkStands => $this->whatBecameOfIt($stack, $session, $followed->job, $for),
            notHeld: fn(): SaysWhereTheWorkStands => $this->withoutASession($for),
        );

        if (! array_key_exists($for->name, $this->followed) || $this->followed[$for->name] !== $followed) {
            return $report;
        }

        if (! $report->stands->isWorking()) {
            $this->hearWhatTheySaid();
            $this->followed[$for->name] = $this->followed[$for->name]->finished();
            $this->letGoUnlessAnotherRuns();
        }

        return $this->became[$for->name] = $report;
    }

    /**
     * Take what every running job said since the last wake, in one reading.
     *
     * A stream that could not be heard keeps what was said, because each
     * job's own report says whether anything stood in its way; a session the
     * stream refused is let go of.
     */
    private function hearWhatTheySaid(): void
    {
        $running = $this->running();

        if ($running === []) {
            return;
        }

        $stack = $this->stack();
        $jobs = array_values(array_map(static fn(AJobFollowed $followed): Job => $followed->job, $running));
        $heard = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheJobsSaid => $this->hearingTheJobs->whatTheySaid($stack, $session, ...$jobs),
            notHeld: static fn(): WhatTheJobsSaid => WhatTheJobsSaid::nothingNew(),
        );

        $heard->either(
            heard: function (WhatTheJobsSaid $said) use ($running): WhatTheJobsSaid {
                foreach ($running as $for => $followed) {
                    $this->followed[$for] = $followed->heard($said->from($followed->job));
                }

                return $said;
            },
            met: function (Obstacle $why) use ($stack, $heard): WhatTheJobsSaid {
                $this->letGoOfTheSession($why, $stack);

                return $heard;
            },
        );
    }

    /** Let go of the jobs' subscription where no job is left running. */
    private function letGoUnlessAnotherRuns(): void
    {
        if ($this->running() === []) {
            $this->hearingTheJobs->letGo();
        }
    }

    /** @return array<string, AJobFollowed> */
    private function running(): array
    {
        return array_filter($this->followed, static fn(AJobFollowed $followed): bool => $followed->running);
    }
}
