<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_shift;
use function array_values;

use Closure;
use Modules\Kernel\Api\AGuardAskedFor;
use Modules\Kernel\Api\Guarding;
use Modules\Kernel\Api\HowTheGuardIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;

/**
 * A stack that guards its data location without one existing.
 *
 * {@see AStackThatTakesCopies} for a guard: the counters are what let a test
 * say what was started, how often it was asked after, and whether it was let
 * go, which no rendered value can see. Asked after more than once, it answers
 * each standing it was given in turn and then stays on the last.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatGuards implements Guarding
{
    public const string THE_JOB = 'a-guard-a-test-can-name';

    /** @var list<AGuardAskedFor> */
    private array $started = [];

    /** @var list<Job> */
    private array $followed = [];

    /** @var list<Job> */
    private array $letGo = [];

    /**
     * @param Closure(): Underway      $starting
     * @param HowTheGuardIsGoing       $now      what it says the next time it is asked
     * @param list<HowTheGuardIsGoing> $then     what it says after that, in turn
     */
    private function __construct(
        private readonly Closure $starting,
        private HowTheGuardIsGoing $now,
        private array $then,
    ) {}

    /** A stack that starts the guard and, asked after it, says `$first` and then each of `$then` in turn. */
    public static function whichGuarded(HowTheGuardIsGoing $first, HowTheGuardIsGoing ...$then): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            $first,
            array_values($then),
        );
    }

    /** A stack that meets every question with the same obstacle. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): Underway => Underway::met($why), HowTheGuardIsGoing::met($why), []);
    }

    public function guard(Stack $stack, Session $session, AGuardAskedFor $asked): Underway
    {
        $this->started[] = $asked;

        return ($this->starting)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheGuardIsGoing
    {
        $this->followed[] = $job;

        return $this->next();
    }

    public function letGo(Stack $stack, Session $session, Job $job): HowTheGuardIsGoing
    {
        $this->letGo[] = $job;

        return HowTheGuardIsGoing::ended();
    }

    /** @return list<AGuardAskedFor> every guard asked for, in order */
    public function started(): array
    {
        return $this->started;
    }

    /** @return list<Job> every name a guard was asked after by, in order */
    public function followed(): array
    {
        return $this->followed;
    }

    /** @return list<Job> every name a guard was let go by, in order */
    public function letGoOf(): array
    {
        return $this->letGo;
    }

    /** The next standing, staying on the last once the rest are spent. */
    private function next(): HowTheGuardIsGoing
    {
        $now = $this->now;

        if ($this->then !== []) {
            $this->now = array_shift($this->then);
        }

        return $now;
    }
}
