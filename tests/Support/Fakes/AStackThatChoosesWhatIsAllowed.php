<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\AnAllowanceChange;
use Modules\Kernel\Api\ChoosingWhatTheHouseholdMayAsk;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAllowanceAsChanged;
use Modules\Kernel\Api\WhatBecameOfTheWork;

/**
 * A stack that rehearses a change to what the household may ask for, takes a yes on, and says what became of each, as a test arranges.
 */
final class AStackThatChoosesWhatIsAllowed implements ChoosingWhatTheHouseholdMayAsk
{
    /** The handle the rehearsal is answered with. */
    public const string THE_REHEARSAL = 'a-rehearsal-of-the-allowance';

    /** The handle the yes is answered with. */
    public const string THE_CHANGE = 'a-change-to-the-allowance';

    /** @var list<AnAllowanceChange> */
    private array $rehearsed = [];

    /** @var list<AnAllowanceChange> */
    private array $carriedOut = [];

    /** @var list<Job> */
    private array $followed = [];

    /**
     * @param Closure(): WhatBecameOfTheWork<TheAllowanceAsChanged>    $rehearsing
     * @param Closure(): WhatBecameOfTheWork<TheAllowanceAsChanged>    $carrying
     * @param Closure(Job): WhatBecameOfTheWork<TheAllowanceAsChanged> $becoming
     */
    private function __construct(
        private readonly Closure $rehearsing,
        private readonly Closure $carrying,
        private readonly Closure $becoming,
    ) {}

    /**
     * A stack that takes both on, and says `$rehearsal` of the rehearsal and `$change` of the yes.
     *
     * @param WhatBecameOfTheWork<TheAllowanceAsChanged> $rehearsal
     * @param WhatBecameOfTheWork<TheAllowanceAsChanged> $change
     */
    public static function answering(WhatBecameOfTheWork $rehearsal, WhatBecameOfTheWork $change): self
    {
        return new self(
            static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway(Job::named(self::THE_REHEARSAL)),
            static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway(Job::named(self::THE_CHANGE)),
            static fn(Job $job): WhatBecameOfTheWork => $job->shown() === self::THE_CHANGE ? $change : $rehearsal,
        );
    }

    /**
     * A stack that answers asking either with `$said` at once, taking nothing on.
     *
     * @param WhatBecameOfTheWork<TheAllowanceAsChanged> $said
     */
    public static function sayingAtOnce(WhatBecameOfTheWork $said): self
    {
        return new self(
            static fn(): WhatBecameOfTheWork => $said,
            static fn(): WhatBecameOfTheWork => $said,
            static fn(): WhatBecameOfTheWork => $said,
        );
    }

    /**
     * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
     */
    public function rehearse(Stack $stack, Session $session, AnAllowanceChange $change): WhatBecameOfTheWork
    {
        $this->rehearsed[] = $change;

        return ($this->rehearsing)();
    }

    /**
     * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
     */
    public function carryOut(Stack $stack, Session $session, AnAllowanceChange $change): WhatBecameOfTheWork
    {
        $this->carriedOut[] = $change;

        return ($this->carrying)();
    }

    /**
     * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        $this->followed[] = $job;

        return ($this->becoming)($job);
    }

    /** @return list<AnAllowanceChange> every change a rehearsal was asked for, in order */
    public function rehearsed(): array
    {
        return $this->rehearsed;
    }

    /** @return list<AnAllowanceChange> every change a yes was sent for, in order */
    public function carriedOut(): array
    {
        return $this->carriedOut;
    }

    /** @return list<Job> every handle asked after, in order */
    public function followed(): array
    {
        return $this->followed;
    }
}
