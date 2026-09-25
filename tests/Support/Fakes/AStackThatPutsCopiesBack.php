<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\HowPuttingItBackIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PuttingBack;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatPuttingItBackWouldDo;
use Modules\Kernel\Api\WhatTheRestoreRehearsalFound;

/**
 * A stack that lists copies and puts them back without one existing.
 *
 * The counters are what let a test say which copy was rehearsed, which
 * listing a yes was sent against and what was followed.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatPutsCopiesBack implements PuttingBack
{
    public const string THE_JOB = 'a-restore-a-test-can-name';

    /** @var list<ACopy> */
    private array $rehearsed = [];

    /** @var list<WhatPuttingItBackWouldDo> */
    private array $agreed = [];

    /** @var list<Job> */
    private array $followed = [];

    /**
     * @param Closure(): WhatTheRestoreRehearsalFound $listing
     * @param Closure(): Underway                     $putting
     * @param Closure(): HowPuttingItBackIsGoing      $becoming
     */
    private function __construct(
        private readonly Closure $listing,
        private readonly Closure $putting,
        private readonly Closure $becoming,
    ) {}

    /** A stack that lists the copy, takes a yes on and, asked after it, says `$became`. */
    public static function listing(WhatPuttingItBackWouldDo $listing, HowPuttingItBackIsGoing $became): self
    {
        return new self(
            static fn(): WhatTheRestoreRehearsalFound => WhatTheRestoreRehearsalFound::listed($listing),
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(): HowPuttingItBackIsGoing => $became,
        );
    }

    /**
     * A stack that lists the copy and refuses the yes.
     *
     * The shape a test needs to reach a refused yes at all: a stack refusing
     * the listing too never hands over anything to agree to.
     */
    public static function listingButRefusing(WhatPuttingItBackWouldDo $listing, Obstacle $why): self
    {
        return new self(
            static fn(): WhatTheRestoreRehearsalFound => WhatTheRestoreRehearsalFound::listed($listing),
            static fn(): Underway => Underway::met($why),
            static fn(): HowPuttingItBackIsGoing => HowPuttingItBackIsGoing::met($why),
        );
    }

    /** A stack that meets every question with the same obstacle. */
    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): WhatTheRestoreRehearsalFound => WhatTheRestoreRehearsalFound::met($why),
            static fn(): Underway => Underway::met($why),
            static fn(): HowPuttingItBackIsGoing => HowPuttingItBackIsGoing::met($why),
        );
    }

    public function rehearse(Stack $stack, Session $session, ACopy $copy): WhatTheRestoreRehearsalFound
    {
        $this->rehearsed[] = $copy;

        return ($this->listing)();
    }

    public function putBack(Stack $stack, Session $session, WhatPuttingItBackWouldDo $listed): Underway
    {
        $this->agreed[] = $listed;

        return ($this->putting)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowPuttingItBackIsGoing
    {
        $this->followed[] = $job;

        return ($this->becoming)();
    }

    /** @return list<ACopy> every copy a rehearsal was asked for, in order */
    public function rehearsed(): array
    {
        return $this->rehearsed;
    }

    /** @return list<WhatPuttingItBackWouldDo> every listing a yes was sent against, in order */
    public function agreed(): array
    {
        return $this->agreed;
    }

    /** @return list<Job> every handle a restore was asked after by, in order */
    public function followed(): array
    {
        return $this->followed;
    }
}
