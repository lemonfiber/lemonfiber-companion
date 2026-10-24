<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\ACheckThatDisturbs;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\RunningACheck;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatARunCovers;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Tests\Support\TheWordCarriedOut;

/**
 * A stack that takes running checks that disturb on and says what became of it, as a test arranges, remembering each run it was asked for.
 */
final class AStackThatRunsChecks implements RunningACheck
{
    /** The handle a run is answered with. */
    public const string THE_RUN = 'a-run';

    /** What {@see self::ran()} records for a run of every check, which names no check or group. */
    public const string EVERY_CHECK = '(every check)';

    /** @var list<string> */
    private array $ran = [];

    /** @var list<Job> */
    private array $followed = [];

    /**
     * @param Closure(): WhatBecameOfTheWork<Report>    $running
     * @param Closure(Job): WhatBecameOfTheWork<Report> $becoming
     */
    private function __construct(private readonly Closure $running, private readonly Closure $becoming) {}

    /**
     * A stack that takes it on, and says `$became` of it once followed.
     *
     * @param WhatBecameOfTheWork<Report> $became
     */
    public static function answering(WhatBecameOfTheWork $became): self
    {
        return new self(
            static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway(Job::named(self::THE_RUN)),
            static fn(): WhatBecameOfTheWork => $became,
        );
    }

    /**
     * A stack that answers asking with `$said` at once, taking nothing on.
     *
     * @param WhatBecameOfTheWork<Report> $said
     */
    public static function sayingAtOnce(WhatBecameOfTheWork $said): self
    {
        return new self(
            static fn(): WhatBecameOfTheWork => $said,
            static fn(): WhatBecameOfTheWork => $said,
        );
    }

    /**
     * @return WhatBecameOfTheWork<Report>
     */
    public function run(Stack $stack, Session $session, ACheckThatDisturbs $check): WhatBecameOfTheWork
    {
        $this->ran[] = $check->narrowedTo();

        return ($this->running)();
    }

    /**
     * @return WhatBecameOfTheWork<Report>
     */
    public function runEvery(Stack $stack, Session $session, WhatARunCovers $covers): WhatBecameOfTheWork
    {
        $this->ran[] = $covers->either(
            every: static fn(): TheWordCarriedOut => new TheWordCarriedOut(self::EVERY_CHECK),
            family: static fn(Category $family): TheWordCarriedOut => new TheWordCarriedOut($family->value),
        )->said;

        return ($this->running)();
    }

    /**
     * @return WhatBecameOfTheWork<Report>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        $this->followed[] = $job;

        return ($this->becoming)($job);
    }

    /** @return list<string> every check, group or family a run was sent for, in order, and {@see self::EVERY_CHECK} for a run of all */
    public function ran(): array
    {
        return $this->ran;
    }

    /** @return list<Job> every handle asked after, in order */
    public function followed(): array
    {
        return $this->followed;
    }
}
