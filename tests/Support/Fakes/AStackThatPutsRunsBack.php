<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\ARunAgreedTo;
use Modules\Kernel\Api\HowPuttingARunBackIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PuttingARunBack;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;

/**
 * A stack that puts runs back without one having been made.
 *
 * The counters are what let a test say which run a yes was sent for and what
 * was followed.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatPutsRunsBack implements PuttingARunBack
{
    public const string THE_JOB = 'an-undo-a-test-can-name';

    /** @var list<ARunAgreedTo> */
    private array $agreed = [];

    /** @var list<Job> */
    private array $followed = [];

    /**
     * @param Closure(): Underway                  $putting
     * @param Closure(): HowPuttingARunBackIsGoing $becoming
     */
    private function __construct(
        private readonly Closure $putting,
        private readonly Closure $becoming,
    ) {}

    /** A stack that takes a yes on and, asked after it, says `$became`. */
    public static function saying(HowPuttingARunBackIsGoing $became): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(): HowPuttingARunBackIsGoing => $became,
        );
    }

    /** A stack that meets every question with the same obstacle. */
    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): Underway => Underway::met($why),
            static fn(): HowPuttingARunBackIsGoing => HowPuttingARunBackIsGoing::met($why),
        );
    }

    public function putBack(Stack $stack, Session $session, ARunAgreedTo $agreed): Underway
    {
        $this->agreed[] = $agreed;

        return ($this->putting)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowPuttingARunBackIsGoing
    {
        $this->followed[] = $job;

        return ($this->becoming)();
    }

    /** @return list<ARunAgreedTo> every run a yes was sent for, in order */
    public function agreed(): array
    {
        return $this->agreed;
    }

    /** @return list<Job> every handle it was asked after by, in order */
    public function followed(): array
    {
        return $this->followed;
    }
}
