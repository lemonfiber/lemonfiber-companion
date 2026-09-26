<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\ABundleAsked;
use Modules\Kernel\Api\AskingForHelp;
use Modules\Kernel\Api\HowTheBundleIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Underway;

/**
 * A stack that gathers support bundles without one existing.
 *
 * {@see AStackThatTakesCopies} for bundles: the counters are what let a test
 * say what was sent and what was followed, which no rendered value can see.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatBundles implements AskingForHelp
{
    public const string THE_JOB = 'a-bundle-a-test-can-name';

    /** @var list<ABundleAsked> */
    private array $asked = [];

    /** @var list<Job> */
    private array $followed = [];

    /**
     * @param Closure(): Underway            $asking
     * @param Closure(): HowTheBundleIsGoing $becoming
     */
    private function __construct(
        private readonly Closure $asking,
        private readonly Closure $becoming,
    ) {}

    /** A stack that takes the bundle on and, asked after it, says `$became`. */
    public static function whichGathered(HowTheBundleIsGoing $became): self
    {
        return new self(
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(): HowTheBundleIsGoing => $became,
        );
    }

    /**
     * A stack that takes the first bundle on and, asked after it, says
     * `$became`, and meets every later one with `$why`.
     */
    public static function whichGatheredOnceThenMet(HowTheBundleIsGoing $became, Obstacle $why): self
    {
        $asked = 0;

        return new self(
            static function () use (&$asked, $why): Underway {
                $asked++;

                return $asked === 1 ? Underway::as(Job::named(self::THE_JOB)) : Underway::met($why);
            },
            static fn(): HowTheBundleIsGoing => $became,
        );
    }

    /** A stack that meets every question with the same obstacle. */
    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): Underway => Underway::met($why),
            static fn(): HowTheBundleIsGoing => HowTheBundleIsGoing::met($why),
        );
    }

    public function ask(Stack $stack, Session $session, ABundleAsked $asked): Underway
    {
        $this->asked[] = $asked;

        return ($this->asking)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheBundleIsGoing
    {
        $this->followed[] = $job;

        return ($this->becoming)();
    }

    /** @return list<ABundleAsked> every bundle asked for, in order */
    public function asked(): array
    {
        return $this->asked;
    }

    /** @return list<Job> every handle a bundle was asked after by, in order */
    public function followed(): array
    {
        return $this->followed;
    }
}
