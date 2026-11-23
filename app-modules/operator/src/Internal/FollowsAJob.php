<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\AJobFollowed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatAJobSaid;
use Modules\Operator\Internal\Presenters\HowWhatAJobSaidReads;
use Modules\Operator\Internal\ViewModels\SaysWhereTheWorkStands;
use Modules\Operator\Internal\ViewModels\WhatAJobSaidAsShown;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * A screen following one job the stack named, to its end.
 *
 * It asks after the job on {@see HowOftenAScreenLooks::WHILE_WORK_RUNS_MS} while
 * the job runs, and on the same wakes takes the lines the job said on the
 * stack's event stream, on a subscription of its own. What became of the job is
 * the screen's own report, asked through {@see whatBecameOfIt()}; the lines are
 * drawn by `<x-operator::following-a-job>`. Lines no job said stay on the start
 * screen.
 *
 * It reads the screen's own `$hearingAJob` and `$storage`, and lets go of a
 * refused session through the screen's {@see \Modules\Connection\Api\LetsGoOfARefusedSession}.
 *
 * @template TReport of SaysWhereTheWorkStands
 *
 * @phpstan-require-extends NativeComponent
 * @phpstan-require-implements AwaitsAnOutcome
 */
trait FollowsAJob
{
    /** The job followed, while there is one. Not shown and never kept past this screen. */
    public ?AJobFollowed $followed = null;

    /** @var TReport|null what became of the job, once this frame has asked */
    public ?SaysWhereTheWorkStands $became = null;

    /** Whether every line the job said is open, rather than only its newest. */
    public bool $showingAllItSaid = false;

    abstract public function stack(): Stack;

    /** Whether the job ran when last asked after, answered without asking the stack. */
    public function awaitsAnOutcome(): bool
    {
        return $this->followed?->running === true;
    }

    /** Take what the job said since the last wake, and ask after it again, while it runs. */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        $followed = $this->followed;

        if (! $followed instanceof AJobFollowed || ! $followed->running) {
            return;
        }

        $this->followed = $this->withWhatItSaid($followed);
        $this->became = null;
    }

    public function showAllItSaid(): void
    {
        $this->showingAllItSaid = true;
    }

    public function showTheNewestItSaid(): void
    {
        $this->showingAllItSaid = false;
    }

    /** What the job has said so far, as the component draws it. */
    public function whatItSays(): WhatAJobSaidAsShown
    {
        return new HowWhatAJobSaidReads()->of($this->followed->said ?? WhatAJobSaid::nothing(), $this->showingAllItSaid);
    }

    /** Follow this job from here on, forgetting the one before and everything it said. */
    protected function follow(Job $job): void
    {
        $this->forget();
        $this->followed = AJobFollowed::named($job);
    }

    /** Follow nothing. */
    protected function forget(): void
    {
        $this->hearingAJob->letGo();
        $this->followed = null;
        $this->became = null;
        $this->showingAllItSaid = false;
    }

    /**
     * What became of the job followed, asked once per frame.
     *
     * @return TReport
     */
    protected function whatBecameOfTheJob(): SaysWhereTheWorkStands
    {
        return $this->became ?? $this->askedAfter();
    }

    /** Let go of the job's subscription as the screen stops, keeping what it said. */
    protected function letGoOfWhatElseItHears(): void
    {
        $this->hearingAJob->letGo();
    }

    /** @return TReport */
    abstract protected function whatBecameOfIt(Stack $stack, Session $session, Job $job): SaysWhereTheWorkStands;

    /** @return TReport */
    abstract protected function whileNothingIsFollowed(): SaysWhereTheWorkStands;

    /** @return TReport */
    abstract protected function withoutASession(): SaysWhereTheWorkStands;

    /**
     * Ask the stack what became of the job followed, and keep whether it still runs.
     *
     * A job that stopped running has its last lines taken and its subscription
     * let go of. A report after which the screen follows another job, or none,
     * is not kept: it was about work the screen has let go of.
     *
     * @return TReport
     */
    private function askedAfter(): SaysWhereTheWorkStands
    {
        $followed = $this->followed;

        if (! $followed instanceof AJobFollowed) {
            return $this->became = $this->askedWhileNothingIsFollowed();
        }

        $stack = $this->stack();
        $report = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): SaysWhereTheWorkStands => $this->whatBecameOfIt($stack, $session, $followed->job),
            notHeld: fn(): SaysWhereTheWorkStands => $this->withoutASession(),
        );

        if ($this->followed !== $followed) {
            return $report;
        }

        if (! $report->stands->isWorking()) {
            $this->followed = $this->withWhatItSaid($followed)->finished();
            $this->hearingAJob->letGo();
        }

        return $this->became = $report;
    }

    /**
     * What the screen reports while it follows no job, which may start one.
     *
     * A job started here that finished on its first reading has said nothing
     * this screen heard, so nothing is taken from the stream for it.
     *
     * @return TReport
     */
    private function askedWhileNothingIsFollowed(): SaysWhereTheWorkStands
    {
        $report = $this->whileNothingIsFollowed();
        $started = $this->followed;

        if ($started instanceof AJobFollowed && ! $report->stands->isWorking()) {
            $this->followed = $started->finished();
        }

        return $report;
    }

    /**
     * The job, with what it said since the last wake.
     *
     * A stream that could not be heard keeps what was said, because the job's
     * own report says whether anything stood in its way; a session the stream
     * refused is let go of.
     */
    private function withWhatItSaid(AJobFollowed $followed): AJobFollowed
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: function (Session $session) use ($stack, $followed): AJobFollowed {
                $heard = $this->hearingAJob->whatItSaid($stack, $session, $followed->job);

                return $heard->either(
                    said: static fn(): AJobFollowed => $followed->heard($heard),
                    nothingNew: static fn(): AJobFollowed => $followed,
                    met: function (Obstacle $why) use ($stack, $followed): AJobFollowed {
                        $this->letGoOfTheSession($why, $stack);

                        return $followed;
                    },
                );
            },
            notHeld: static fn(): AJobFollowed => $followed,
        );
    }
}
