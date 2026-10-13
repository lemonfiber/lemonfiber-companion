<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\AnAllowanceChange;
use Modules\Kernel\Api\ChoosingWhatTheHouseholdMayAsk;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheAllowanceChange;

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
     * @param Closure(): WhatBecameOfTheAllowanceChange    $rehearsing
     * @param Closure(): WhatBecameOfTheAllowanceChange    $carrying
     * @param Closure(Job): WhatBecameOfTheAllowanceChange $becoming
     */
    private function __construct(
        private readonly Closure $rehearsing,
        private readonly Closure $carrying,
        private readonly Closure $becoming,
    ) {}

    /** A stack that takes both on, and says `$rehearsal` of the rehearsal and `$change` of the yes. */
    public static function answering(WhatBecameOfTheAllowanceChange $rehearsal, WhatBecameOfTheAllowanceChange $change): self
    {
        return new self(
            static fn(): WhatBecameOfTheAllowanceChange => WhatBecameOfTheAllowanceChange::underway(Job::named(self::THE_REHEARSAL)),
            static fn(): WhatBecameOfTheAllowanceChange => WhatBecameOfTheAllowanceChange::underway(Job::named(self::THE_CHANGE)),
            static fn(Job $job): WhatBecameOfTheAllowanceChange => $job->shown() === self::THE_CHANGE ? $change : $rehearsal,
        );
    }

    /** A stack that answers asking either with `$said` at once, taking nothing on. */
    public static function sayingAtOnce(WhatBecameOfTheAllowanceChange $said): self
    {
        return new self(
            static fn(): WhatBecameOfTheAllowanceChange => $said,
            static fn(): WhatBecameOfTheAllowanceChange => $said,
            static fn(): WhatBecameOfTheAllowanceChange => $said,
        );
    }

    public function rehearse(Stack $stack, Session $session, AnAllowanceChange $change): WhatBecameOfTheAllowanceChange
    {
        $this->rehearsed[] = $change;

        return ($this->rehearsing)();
    }

    public function carryOut(Stack $stack, Session $session, AnAllowanceChange $change): WhatBecameOfTheAllowanceChange
    {
        $this->carriedOut[] = $change;

        return ($this->carrying)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheAllowanceChange
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
