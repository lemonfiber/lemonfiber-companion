<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\ACopyAsked;
use Modules\Kernel\Api\HowTheCopyIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TakingCopies;
use Modules\Kernel\Api\Underway;

/**
 * A stack that takes copies without one existing.
 *
 * {@see AStackThatKeepsCurrent} for copies: the counters are what let a test
 * say what was sent and what was followed, which no rendered value can see.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatTakesCopies implements TakingCopies
{
    public const string THE_JOB = 'a-copy-a-test-can-name';

    /** @var list<ACopyAsked> */
    private array $taken = [];

    /** @var list<Job> */
    private array $followed = [];

    /**
     * @param Closure(): Underway          $taking
     * @param Closure(): HowTheCopyIsGoing $becoming
     */
    private function __construct(
        private readonly Closure $taking,
        private readonly Closure $becoming,
    ) {}

    /** A stack that takes the copy on and, asked after it, says `$became`. */
    public static function whichTook(HowTheCopyIsGoing $became): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(): HowTheCopyIsGoing => $became,
        );
    }

    /** A stack that meets every question with the same obstacle. */
    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): Underway => Underway::met($why),
            static fn(): HowTheCopyIsGoing => HowTheCopyIsGoing::met($why),
        );
    }

    public function take(Stack $stack, Session $session, ACopyAsked $asked): Underway
    {
        $this->taken[] = $asked;

        return ($this->taking)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheCopyIsGoing
    {
        $this->followed[] = $job;

        return ($this->becoming)();
    }

    /** @return list<ACopyAsked> every copy asked for, in order */
    public function taken(): array
    {
        return $this->taken;
    }

    /** @return list<Job> every handle a copy was asked after by, in order */
    public function followed(): array
    {
        return $this->followed;
    }
}
