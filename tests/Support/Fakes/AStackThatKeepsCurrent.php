<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheUpdateIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KeepingCurrent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatIsCurrent;

/**
 * A stack that answers about its upkeep without one existing.
 *
 * {@see AStackThatSupervises} one port over, and for the same reasons: a screen
 * test that had to stand up a client would be testing the client, and the
 * counters here are what let a test say *it asked once*, which no assertion on
 * a rendered value can see.
 */
final class AStackThatKeepsCurrent implements KeepingCurrent
{
    public const string THE_JOB = 'an-update-a-test-can-name';

    private int $askings = 0;

    private ?Stack $askedAbout = null;

    /** @var list<TakingAnUpdate> */
    private array $taken = [];

    /** @var list<Job> */
    private array $followed = [];

    /**
     * @param Closure(): WhatIsCurrent       $answer
     * @param Closure(): Underway            $taking
     * @param Closure(): HowTheUpdateIsGoing $becoming
     */
    private function __construct(
        private readonly Closure $answer,
        private readonly Closure $taking,
        private readonly Closure $becoming,
    ) {}

    /** A stack whose update, once taken, is still running whenever it is asked after. */
    public static function with(Upkeep $upkeep): self
    {
        return self::whichTook($upkeep, HowTheUpdateIsGoing::stillRunning());
    }

    /** A stack that reads as `$upkeep` and, asked after the update it took, says `$became`. */
    public static function whichTook(Upkeep $upkeep, HowTheUpdateIsGoing $became): self
    {
        return new self(
            static fn(): WhatIsCurrent => WhatIsCurrent::stands($upkeep),
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
            static fn(): HowTheUpdateIsGoing => $became,
        );
    }

    /** A stack with nothing waiting, which is the ordinary evening. */
    public static function withNothingWaiting(): self
    {
        return self::with(Upkeep::reported(
            AgainstThePins::Current,
            Releases::none(),
            Services::none(),
            Services::none(),
            HowServicesTookIt::none(),
        ));
    }

    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): WhatIsCurrent => WhatIsCurrent::met($why),
            static fn(): Underway => Underway::met($why),
            static fn(): HowTheUpdateIsGoing => HowTheUpdateIsGoing::met($why),
        );
    }

    /**
     * Answers the reading and refuses the verb.
     *
     * The shape a test needs to reach the screen's refusal path at all: a stack
     * refusing both halves never hands over an update to agree to, so the
     * assertion about what happens after the yes is never reached and the test
     * passes by re-proving the read.
     */
    public static function withButRefusing(Upkeep $upkeep, Obstacle $why): self
    {
        return new self(
            static fn(): WhatIsCurrent => WhatIsCurrent::stands($upkeep),
            static fn(): Underway => Underway::met($why),
            static fn(): HowTheUpdateIsGoing => HowTheUpdateIsGoing::met($why),
        );
    }

    public function standing(Stack $stack, Session $session): WhatIsCurrent
    {
        ++$this->askings;
        $this->askedAbout = $stack;

        return ($this->answer)();
    }

    public function take(Stack $stack, Session $session, TakingAnUpdate $agreed): Underway
    {
        $this->taken[] = $agreed;

        return ($this->taking)();
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheUpdateIsGoing
    {
        $this->followed[] = $job;

        return ($this->becoming)();
    }

    /** How many times the stack was read, which is the whole of asking once. */
    public function askings(): int
    {
        return $this->askings;
    }

    public function askedAbout(): ?Stack
    {
        return $this->askedAbout;
    }

    /** @return list<TakingAnUpdate> what was actually sent, in order */
    public function taken(): array
    {
        return $this->taken;
    }

    /** @return list<Job> every handle the update was asked after by, in order */
    public function followed(): array
    {
        return $this->followed;
    }
}
