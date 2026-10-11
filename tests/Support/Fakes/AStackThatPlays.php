<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function array_values;

use Modules\Kernel\Api\FollowingWhatIsPlaying;
use Modules\Kernel\Api\HowPlayingWasRead;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatAStreamSaid;
use Modules\Kernel\Api\WhatTheHouseholdIsPlaying;

/**
 * A stack asked what the household is playing, then heard on its stream, as a script of what each wake hears.
 *
 * {@see AStackThatNarrates}'s two endings and its counts: a screen's promise is
 * to ask on its cadence and let go whenever nobody can see it.
 */
final class AStackThatPlays implements FollowingWhatIsPlaying
{
    private int $next = 0;

    private int $asked = 0;

    private int $lettingsGo = 0;

    /**
     * @param list<WhatAStreamSaid<WhatTheHouseholdIsPlaying>> $script
     * @param WhatAStreamSaid<WhatTheHouseholdIsPlaying>       $afterwards
     */
    private function __construct(
        private readonly HowPlayingWasRead $now,
        private array $script,
        private readonly WhatAStreamSaid $afterwards,
    ) {}

    /**
     * Answers asking with `$now`, then says these in turn and stays open, saying nothing.
     *
     * @param WhatAStreamSaid<WhatTheHouseholdIsPlaying> ...$said
     */
    public static function holdingOpen(HowPlayingWasRead $now, WhatAStreamSaid ...$said): self
    {
        return new self($now, array_values($said), WhatAStreamSaid::nothing());
    }

    /**
     * Answers asking with `$now`, then says these in turn and is closed.
     *
     * @param WhatAStreamSaid<WhatTheHouseholdIsPlaying> ...$said
     */
    public static function thenEnding(HowPlayingWasRead $now, WhatAStreamSaid ...$said): self
    {
        return new self($now, array_values($said), WhatAStreamSaid::closed());
    }

    public function now(Stack $stack, Session $session): HowPlayingWasRead
    {
        return $this->now;
    }

    /** @return WhatAStreamSaid<WhatTheHouseholdIsPlaying> */
    public function heard(Stack $stack, Session $session): WhatAStreamSaid
    {
        $this->asked++;

        if (! array_key_exists($this->next, $this->script)) {
            return $this->afterwards;
        }

        return $this->script[$this->next++];
    }

    /** @return WhatAStreamSaid<WhatTheHouseholdIsPlaying> */
    public function letGo(): WhatAStreamSaid
    {
        $this->lettingsGo++;

        return WhatAStreamSaid::closed();
    }

    /** How many times a screen asked what had arrived. */
    public function asked(): int
    {
        return $this->asked;
    }

    /** How many times a screen let go of the subscription. */
    public function lettingsGo(): int
    {
        return $this->lettingsGo;
    }
}
