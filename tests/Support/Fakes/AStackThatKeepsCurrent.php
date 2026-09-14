<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\HowCurrent;
use Modules\Kernel\Api\HowServicesTookIt;
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
 * counters here are what let a test say *it asked once* — which `N1-R17` is
 * about and no assertion on a rendered value can see.
 */
final class AStackThatKeepsCurrent implements KeepingCurrent
{
    public const string THE_JOB = 'an-update-a-test-can-name';

    private int $askings = 0;

    private ?Stack $askedAbout = null;

    /** @var list<TakingAnUpdate> */
    private array $taken = [];

    /**
     * @param Closure(): WhatIsCurrent $answer
     * @param Closure(): Underway      $taking
     */
    private function __construct(
        private readonly Closure $answer,
        private readonly Closure $taking,
    ) {}

    public static function with(Upkeep $upkeep): self
    {
        return new self(
            static fn(): WhatIsCurrent => WhatIsCurrent::stands($upkeep),
            static fn(): Underway => Underway::as(Job::named(self::THE_JOB)),
        );
    }

    /** A stack with nothing waiting, which is the ordinary evening. */
    public static function withNothingWaiting(): self
    {
        return self::with(Upkeep::reported(
            HowCurrent::Current,
            Releases::none(),
            Services::none(),
            HowServicesTookIt::none(),
        ));
    }

    public static function met(Obstacle $why): self
    {
        return new self(
            static fn(): WhatIsCurrent => WhatIsCurrent::met($why),
            static fn(): Underway => Underway::met($why),
        );
    }

    /**
     * Answers the reading and refuses the verb.
     *
     * The shape a test needs to reach the screen's refusal path at all: a stack
     * refusing both halves never hands over a release to agree to, so the
     * assertion about what happens after the yes is never reached and the test
     * passes by re-proving the read.
     */
    public static function withButRefusing(Upkeep $upkeep, Obstacle $why): self
    {
        return new self(
            static fn(): WhatIsCurrent => WhatIsCurrent::stands($upkeep),
            static fn(): Underway => Underway::met($why),
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

    /** How many times the stack was read, which is what `N1-R17` is about. */
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
}
