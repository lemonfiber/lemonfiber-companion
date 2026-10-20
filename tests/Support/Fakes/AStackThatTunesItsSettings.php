<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\ASettingChangeAgreed;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingWhatServicesRead;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatWasFoundOfTheSettings;

/**
 * A stack that lists the settings its services read, rehearses a change to one, takes a yes on, and says what became of each, as a test arranges.
 *
 * It remembers what it was asked, so a test can say the yes carried the
 * rehearsal it was given against, and that nothing was written without one.
 */
final class AStackThatTunesItsSettings implements SettingWhatServicesRead
{
    /** The handle the rehearsal is answered with. */
    public const string THE_REHEARSAL = 'a-rehearsal-of-a-setting';

    /** The handle the yes is answered with. */
    public const string THE_CHANGE = 'a-change-to-a-setting';

    private int $askings = 0;

    /** @var list<ASettingChange> */
    private array $rehearsed = [];

    /** @var list<ASettingChangeAgreed> */
    private array $carriedOut = [];

    /** @var list<Job> */
    private array $followed = [];

    /**
     * @param Closure(): WhatWasFoundOfTheSettings                   $reading
     * @param Closure(): WhatBecameOfTheWork<TheSettingAsChanged>    $rehearsing
     * @param Closure(): WhatBecameOfTheWork<TheSettingAsChanged>    $carrying
     * @param Closure(Job): WhatBecameOfTheWork<TheSettingAsChanged> $becoming
     */
    private function __construct(
        private readonly Closure $reading,
        private readonly Closure $rehearsing,
        private readonly Closure $carrying,
        private readonly Closure $becoming,
    ) {}

    /**
     * A stack listing `$settings`, taking both acts on, and saying `$rehearsal` of the rehearsal and `$change` of the yes.
     *
     * @param WhatBecameOfTheWork<TheSettingAsChanged> $rehearsal
     * @param WhatBecameOfTheWork<TheSettingAsChanged> $change
     */
    public static function answering(TheServicesSettings $settings, WhatBecameOfTheWork $rehearsal, WhatBecameOfTheWork $change): self
    {
        return new self(
            static fn(): WhatWasFoundOfTheSettings => WhatWasFoundOfTheSettings::found($settings),
            static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway(Job::named(self::THE_REHEARSAL)),
            static fn(): WhatBecameOfTheWork => WhatBecameOfTheWork::underway(Job::named(self::THE_CHANGE)),
            static fn(Job $job): WhatBecameOfTheWork => $job->shown() === self::THE_CHANGE ? $change : $rehearsal,
        );
    }

    /**
     * A stack whose listing comes to `$found`, and that answers asking either act with `$said` at once, taking nothing on.
     *
     * @param WhatBecameOfTheWork<TheSettingAsChanged> $said
     */
    public static function sayingAtOnce(WhatWasFoundOfTheSettings $found, WhatBecameOfTheWork $said): self
    {
        return new self(
            static fn(): WhatWasFoundOfTheSettings => $found,
            static fn(): WhatBecameOfTheWork => $said,
            static fn(): WhatBecameOfTheWork => $said,
            static fn(): WhatBecameOfTheWork => $said,
        );
    }

    public function readOn(Stack $stack, Session $session): WhatWasFoundOfTheSettings
    {
        $this->askings++;

        return ($this->reading)();
    }

    /**
     * @return WhatBecameOfTheWork<TheSettingAsChanged>
     */
    public function rehearse(Stack $stack, Session $session, ASettingChange $change): WhatBecameOfTheWork
    {
        $this->rehearsed[] = $change;

        return ($this->rehearsing)();
    }

    /**
     * @return WhatBecameOfTheWork<TheSettingAsChanged>
     */
    public function carryOut(Stack $stack, Session $session, ASettingChangeAgreed $agreed): WhatBecameOfTheWork
    {
        $this->carriedOut[] = $agreed;

        return ($this->carrying)();
    }

    /**
     * @return WhatBecameOfTheWork<TheSettingAsChanged>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork
    {
        $this->followed[] = $job;

        return ($this->becoming)($job);
    }

    /** How many times the settings were read. */
    public function askings(): int
    {
        return $this->askings;
    }

    /** @return list<ASettingChange> every change a rehearsal was asked for, in order */
    public function rehearsed(): array
    {
        return $this->rehearsed;
    }

    /** @return list<ASettingChangeAgreed> every yes sent, in order */
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
