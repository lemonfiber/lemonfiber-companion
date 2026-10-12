<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\ALineChange;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SharingTheLine;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheLineChange;

/**
 * A stack that rehearses a change to its line, takes a yes on, and says what became of each, as a test arranges.
 *
 * It remembers what it was asked, so a test can say the yes sent the change
 * the rehearsal was asked with, and that nothing was made without one.
 */
final class AStackThatSharesItsLine implements SharingTheLine
{
    /** The handle the rehearsal is answered with. */
    public const string THE_REHEARSAL = 'a-rehearsal-of-the-line';

    /** The handle the yes is answered with. */
    public const string THE_CHANGE = 'a-change-to-the-line';

    /** @var list<ALineChange> */
    private array $rehearsed = [];

    /** @var list<ALineChange> */
    private array $carriedOut = [];

    /** @var list<Job> */
    private array $followed = [];

    /**
     * @param Closure(): WhatBecameOfTheLineChange    $rehearsing
     * @param Closure(): WhatBecameOfTheLineChange    $carrying
     * @param Closure(Job): WhatBecameOfTheLineChange $becoming
     */
    private function __construct(
        private readonly Closure $rehearsing,
        private readonly Closure $carrying,
        private readonly Closure $becoming,
    ) {}

    /** A stack that takes both on, and says `$rehearsal` of the rehearsal and `$change` of the yes. */
    public static function answering(WhatBecameOfTheLineChange $rehearsal, WhatBecameOfTheLineChange $change): self
    {
        return new self(
            static fn(): WhatBecameOfTheLineChange => WhatBecameOfTheLineChange::underway(Job::named(self::THE_REHEARSAL)),
            static fn(): WhatBecameOfTheLineChange => WhatBecameOfTheLineChange::underway(Job::named(self::THE_CHANGE)),
            static fn(Job $job): WhatBecameOfTheLineChange => $job->shown() === self::THE_CHANGE ? $change : $rehearsal,
        );
    }

    /** A stack that answers asking either with `$said` at once, taking nothing on. */
    public static function sayingAtOnce(WhatBecameOfTheLineChange $said): self
    {
        return new self(
            static fn(): WhatBecameOfTheLineChange => $said,
            static fn(): WhatBecameOfTheLineChange => $said,
            static fn(): WhatBecameOfTheLineChange => $said,
        );
    }

    public function rehearse(Stack $stack, Session $session, ALineChange $change): WhatBecameOfTheLineChange
    {
        $this->rehearsed[] = $change;

        return ($this->rehearsing)();
    }

    public function carryOut(Stack $stack, Session $session, ALineChange $change): WhatBecameOfTheLineChange
    {
        $this->carriedOut[] = $change;

        return ($this->carrying)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheLineChange
    {
        $this->followed[] = $job;

        return ($this->becoming)($job);
    }

    /** @return list<ALineChange> every change a rehearsal was asked for, in order */
    public function rehearsed(): array
    {
        return $this->rehearsed;
    }

    /** @return list<ALineChange> every change a yes was sent for, in order */
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
