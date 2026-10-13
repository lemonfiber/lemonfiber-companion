<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Switching;
use Modules\Kernel\Api\TheFormsToSwitchTo;
use Modules\Kernel\Api\TheSwitch;
use Modules\Kernel\Api\WhatBecameOfTheWork;

/**
 * A stack that rehearses a switch of the active forms, takes a yes on, and says what became of each, as a test arranges.
 */
final class AStackThatSwitches implements Switching
{
    /** The handle the rehearsal is answered with. */
    public const string THE_REHEARSAL = 'a-rehearsal-of-the-switch';

    /** The handle the yes is answered with. */
    public const string THE_SWITCH = 'a-switch';

    /** @var list<TheFormsToSwitchTo> */
    private array $rehearsed = [];

    /** @var list<TheFormsToSwitchTo> */
    private array $carriedOut = [];

    /** @var list<Job> */
    private array $followed = [];

    /**
     * @param Closure(): WhatBecameOfTheWork<TheSwitch>    $rehearsing
     * @param Closure(): WhatBecameOfTheWork<TheSwitch>    $carrying
     * @param Closure(Job): WhatBecameOfTheWork<TheSwitch> $becoming
     */
    private function __construct(
        private readonly Closure $rehearsing,
        private readonly Closure $carrying,
        private readonly Closure $becoming,
    ) {}

    /**
     * A stack that takes both on, and says `$rehearsal` of the rehearsal and `$switch` of the yes.
     *
     * @param WhatBecameOfTheWork<TheSwitch> $rehearsal
     * @param WhatBecameOfTheWork<TheSwitch> $switch
     */
    public static function answering(WhatBecameOfTheWork $rehearsal, WhatBecameOfTheWork $switch): self
    {
        return new self(
            static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway(Job::named(self::THE_REHEARSAL)),
            static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway(Job::named(self::THE_SWITCH)),
            static fn(Job $job): WhatBecameOfTheWork => $job->shown() === self::THE_SWITCH ? $switch : $rehearsal,
        );
    }

    /**
     * A stack that answers asking either with `$said` at once, taking nothing on.
     *
     * @param WhatBecameOfTheWork<TheSwitch> $said
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
     * @return WhatBecameOfTheWork<TheSwitch>
     */
    public function rehearse(Stack $stack, Session $session, TheFormsToSwitchTo $forms): WhatBecameOfTheWork
    {
        $this->rehearsed[] = $forms;

        return ($this->rehearsing)();
    }

    /**
     * @return WhatBecameOfTheWork<TheSwitch>
     */
    public function carryOut(Stack $stack, Session $session, TheFormsToSwitchTo $forms): WhatBecameOfTheWork
    {
        $this->carriedOut[] = $forms;

        return ($this->carrying)();
    }

    /**
     * @return WhatBecameOfTheWork<TheSwitch>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        $this->followed[] = $job;

        return ($this->becoming)($job);
    }

    /** @return list<TheFormsToSwitchTo> every set a rehearsal was asked for, in order */
    public function rehearsed(): array
    {
        return $this->rehearsed;
    }

    /** @return list<TheFormsToSwitchTo> every set a yes was sent for, in order */
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
