<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function array_shift;

use Closure;
use Modules\Kernel\Api\HowPausingIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PausingDownloads;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\WhatPausingComesTo;
use Modules\Kernel\Api\WhatToDoWithTheDownloads;

/**
 * A stack that rehearses pausing or resuming its downloads, takes a yes on, and says what became of each, as a test arranges.
 *
 * It remembers what it was asked, so a test can say the yes quoted the offer
 * the rehearsal named, and that nothing was carried out without one.
 */
final class AStackThatPausesDownloads implements PausingDownloads
{
    /** The handle the rehearsal is answered with. */
    public const string THE_REHEARSAL = 'a-rehearsal-a-test-can-name';

    /** The handle a second rehearsal is answered with. */
    public const string THE_NEXT_REHEARSAL = 'a-second-rehearsal-a-test-can-name';

    /** The handle the yes is answered with. */
    public const string THE_JOB = 'a-pause-a-test-can-name';

    /** @var list<WhatToDoWithTheDownloads> */
    private array $rehearsed = [];

    /** @var list<WhatPausingComesTo> */
    private array $agreed = [];

    /** @var list<Job> */
    private array $followed = [];

    /**
     * @param Closure(): Underway          $rehearsing
     * @param Closure(): Underway          $carrying
     * @param Closure(Job): HowPausingIsGoing $becoming
     */
    private function __construct(
        private readonly Closure $rehearsing,
        private readonly Closure $carrying,
        private readonly Closure $becoming,
    ) {}

    /** A stack whose rehearsal comes to `$offer`, that takes a yes on and, asked after it, says `$became`. */
    public static function rehearsing(HowPausingIsGoing $offer, HowPausingIsGoing $became): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_REHEARSAL)),
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(Job $job): HowPausingIsGoing => $job->shown() === self::THE_JOB ? $became : $offer,
        );
    }

    /** A stack whose first rehearsal comes to `$first` and every one after to `$then`, each under a handle of its own, that takes a yes on. */
    public static function rehearsingAfresh(WhatPausingComesTo $first, WhatPausingComesTo $then): self
    {
        $handles = [self::THE_REHEARSAL, self::THE_NEXT_REHEARSAL];
        $rehearsals = [self::THE_REHEARSAL => $first, self::THE_NEXT_REHEARSAL => $then];

        return new self(
            static function () use (&$handles): Underway {
                return Underway::as(Job::named(array_shift($handles) ?? self::THE_NEXT_REHEARSAL));
            },
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(Job $job): HowPausingIsGoing => array_key_exists($job->shown(), $rehearsals) ? HowPausingIsGoing::answered($rehearsals[$job->shown()]) : HowPausingIsGoing::stillRunning(),
        );
    }

    /**
     * A stack that rehearses and refuses the yes.
     *
     * The shape a test needs to reach a refused yes at all: a stack refusing
     * the rehearsal too never hands over anything to agree to.
     */
    public static function rehearsingButRefusing(WhatPausingComesTo $offer, Obstacle $why): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_REHEARSAL)),
            static fn(): Underway => Underway::met($why),
            static fn(Job $job): HowPausingIsGoing => $job->shown() === self::THE_JOB ? HowPausingIsGoing::met($why) : HowPausingIsGoing::answered($offer),
        );
    }

    /** A stack that meets every question with the same obstacle. */
    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): Underway => Underway::met($why),
            static fn(): Underway => Underway::met($why),
            static fn(): HowPausingIsGoing => HowPausingIsGoing::met($why),
        );
    }

    public function rehearse(Stack $stack, Session $session, WhatToDoWithTheDownloads $asked): Underway
    {
        $this->rehearsed[] = $asked;

        return ($this->rehearsing)();
    }

    public function carryOut(Stack $stack, Session $session, WhatPausingComesTo $offer): Underway
    {
        $this->agreed[] = $offer;

        return ($this->carrying)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowPausingIsGoing
    {
        $this->followed[] = $job;

        return ($this->becoming)($job);
    }

    /** @return list<WhatToDoWithTheDownloads> every direction a rehearsal was asked for, in order */
    public function rehearsed(): array
    {
        return $this->rehearsed;
    }

    /** @return list<WhatPausingComesTo> every offer a yes was sent against, in order */
    public function agreed(): array
    {
        return $this->agreed;
    }

    /** @return list<Job> every handle asked after, in order */
    public function followed(): array
    {
        return $this->followed;
    }
}
