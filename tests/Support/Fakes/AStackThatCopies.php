<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function array_values;

use Modules\Kernel\Api\FollowingTheCopyRunning;
use Modules\Kernel\Api\HowTheCopyRunningWasRead;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatAStreamSaid;
use Modules\Kernel\Api\WhatIsBeingCopied;

/**
 * A stack asked what is being copied on it, then heard on its stream, as a script of what each wake hears.
 *
 * {@see AStackThatPlays}'s shape: a screen's promise is to read once when it
 * opens, ask on its cadence, and let go whenever nobody can see it.
 */
final class AStackThatCopies implements FollowingTheCopyRunning
{
    private int $next = 0;

    private int $read = 0;

    private int $asked = 0;

    private int $lettingsGo = 0;

    /**
     * @param list<WhatAStreamSaid<WhatIsBeingCopied>> $script
     * @param WhatAStreamSaid<WhatIsBeingCopied>       $afterwards
     */
    private function __construct(
        private readonly HowTheCopyRunningWasRead $now,
        private array $script,
        private readonly WhatAStreamSaid $afterwards,
    ) {}

    /**
     * Answers asking with `$now`, then says these in turn and stays open, saying nothing.
     *
     * @param WhatAStreamSaid<WhatIsBeingCopied> ...$said
     */
    public static function holdingOpen(HowTheCopyRunningWasRead $now, WhatAStreamSaid ...$said): self
    {
        return new self($now, array_values($said), WhatAStreamSaid::nothing());
    }

    /** Answers that nothing is being copied, and stays open, saying nothing. */
    public static function copyingNothing(): self
    {
        return self::holdingOpen(HowTheCopyRunningWasRead::said(WhatIsBeingCopied::nothing()));
    }

    /**
     * Answers asking with `$now`, then says these in turn and is closed.
     *
     * @param WhatAStreamSaid<WhatIsBeingCopied> ...$said
     */
    public static function thenEnding(HowTheCopyRunningWasRead $now, WhatAStreamSaid ...$said): self
    {
        return new self($now, array_values($said), WhatAStreamSaid::closed());
    }

    public function now(Stack $stack, Session $session): HowTheCopyRunningWasRead
    {
        $this->read++;

        return $this->now;
    }

    /** @return WhatAStreamSaid<WhatIsBeingCopied> */
    public function heard(Stack $stack, Session $session): WhatAStreamSaid
    {
        $this->asked++;

        if (! array_key_exists($this->next, $this->script)) {
            return $this->afterwards;
        }

        return $this->script[$this->next++];
    }

    /** @return WhatAStreamSaid<WhatIsBeingCopied> */
    public function letGo(): WhatAStreamSaid
    {
        $this->lettingsGo++;

        return WhatAStreamSaid::closed();
    }

    /** How many times a screen asked what is being copied now. */
    public function read(): int
    {
        return $this->read;
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
