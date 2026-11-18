<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RunningOnASchedule;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheScheduledServices;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatRunningItNowCameTo;
use Modules\Kernel\Api\WhatRunsOnASchedule;

/**
 * A stack where a test says how its run-and-exit services last ran and what running one at once comes to, and which remembers being asked.
 */
final class AStackThatSaysWhatRunsOnASchedule implements RunningOnASchedule
{
    /** The handle a run at once is answered with. */
    public const string THE_RUN = 'a-run-asked-at-once';

    /** How many times it was asked. */
    private int $askings = 0;

    /** @var list<string> */
    private array $runs = [];

    /** @var list<string> */
    private array $followed = [];

    /**
     * @param Closure(): WhatRunsOnASchedule                 $answer
     * @param WhatBecameOfTheWork<WhatRunningItNowCameTo> $sent
     * @param WhatBecameOfTheWork<WhatRunningItNowCameTo> $became
     */
    private function __construct(private readonly Closure $answer, private readonly WhatBecameOfTheWork $sent, private readonly WhatBecameOfTheWork $became) {}

    /** A stack whose run-and-exit services are these. */
    public static function with(TheScheduledServices $said): self
    {
        return new self(static fn(): WhatRunsOnASchedule => WhatRunsOnASchedule::said($said), WhatBecameOfTheWork::ended(), WhatBecameOfTheWork::ended());
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatRunsOnASchedule => WhatRunsOnASchedule::met($why), WhatBecameOfTheWork::ended(), WhatBecameOfTheWork::ended());
    }

    /**
     * The same stack, taking a run at once on under {@see self::THE_RUN} and saying `$became` of it.
     *
     * @param WhatBecameOfTheWork<WhatRunningItNowCameTo> $became
     */
    public function running(WhatBecameOfTheWork $became): self
    {
        return new self($this->answer, WhatBecameOfTheWork::underway(Job::named(self::THE_RUN)), $became);
    }

    /**
     * The same stack, answering a run at once with `$said` before taking anything on.
     *
     * @param WhatBecameOfTheWork<WhatRunningItNowCameTo> $said
     */
    public function answeringTheRun(WhatBecameOfTheWork $said): self
    {
        return new self($this->answer, $said, $this->became);
    }

    /** How many times it was asked. */
    public function askings(): int
    {
        return $this->askings;
    }

    /** @return list<string> each service asked to run at once, in order */
    public function runsAsked(): array
    {
        return $this->runs;
    }

    /** @return list<string> each handle asked after, in order */
    public function followed(): array
    {
        return $this->followed;
    }

    public function scheduled(Stack $stack, Session $session): WhatRunsOnASchedule
    {
        $this->askings++;

        return ($this->answer)();
    }

    /**
     * @return WhatBecameOfTheWork<WhatRunningItNowCameTo>
     */
    public function runNow(Stack $stack, Session $session, ServiceId $service): WhatBecameOfTheWork
    {
        $this->runs[] = $service->named();

        return $this->sent;
    }

    /**
     * @return WhatBecameOfTheWork<WhatRunningItNowCameTo>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        $this->followed[] = $job->shown();

        return $this->became;
    }
}
