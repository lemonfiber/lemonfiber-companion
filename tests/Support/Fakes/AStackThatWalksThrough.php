<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\HowTheWalkthroughIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WalkingThrough;
use Modules\Kernel\Api\WhatToWalk;

/**
 * A stack that walks through fetching one thing without one existing.
 *
 * {@see AStackThatKeepsCurrent} one action over, and for the same reasons: the
 * lists here are what let a test say what was asked for and which handle was
 * followed, which no assertion on a rendered value can see.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatWalksThrough implements WalkingThrough
{
    public const string THE_JOB = 'a-walkthrough-a-test-can-name';

    /** @var list<WhatToWalk> */
    private array $walked = [];

    /** @var list<Job> */
    private array $followed = [];

    /**
     * @param Closure(): Underway                  $starting
     * @param Closure(): HowTheWalkthroughIsGoing  $becoming
     */
    private function __construct(
        private readonly Closure $starting,
        private readonly Closure $becoming,
    ) {}

    /** A stack that starts a walkthrough and, asked after it, says `$became`. */
    public static function whichWalked(HowTheWalkthroughIsGoing $became): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(): HowTheWalkthroughIsGoing => $became,
        );
    }

    /** A stack that could not be reached, for either half. */
    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): Underway => Underway::met($why),
            static fn(): HowTheWalkthroughIsGoing => HowTheWalkthroughIsGoing::met($why),
        );
    }

    public function walk(Stack $stack, Session $session, WhatToWalk $asked): Underway
    {
        $this->walked[] = $asked;

        return ($this->starting)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheWalkthroughIsGoing
    {
        $this->followed[] = $job;

        return ($this->becoming)();
    }

    /** @return list<WhatToWalk> what each walkthrough was asked for, in order */
    public function walked(): array
    {
        return $this->walked;
    }

    /** @return list<Job> every handle a walkthrough was asked after by, in order */
    public function followed(): array
    {
        return $this->followed;
    }
}
