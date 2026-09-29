<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function array_values;

use Modules\Kernel\Api\HearingTheWalk;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheWalkSaid;

/**
 * A stack's event stream while a walk runs, as a script of what each wake of a screen hears.
 *
 * {@see AStackThatSpeaksUp}'s two endings, for its reason: one held open goes
 * quiet once it has said its piece, and one that ends says so once and then
 * waits to be opened again.
 *
 * It counts how often it was asked and let go of, because a screen's promise
 * is about both: asked only on its cadence, and let go of whenever nobody can
 * see it or the walk is over.
 */
final class AStackThatNarrates implements HearingTheWalk
{
    private int $next = 0;

    private int $asked = 0;

    private int $lettingsGo = 0;

    /** @param list<WhatTheWalkSaid> $script */
    private function __construct(
        private array $script,
        private readonly WhatTheWalkSaid $afterwards,
    ) {}

    /** A stream that says these in turn and then stays open, saying nothing. */
    public static function holdingOpen(WhatTheWalkSaid ...$said): self
    {
        return new self(array_values($said), WhatTheWalkSaid::nothing());
    }

    /** A stream that says these in turn and then is closed. */
    public static function thenEnding(WhatTheWalkSaid ...$said): self
    {
        return new self(array_values($said), WhatTheWalkSaid::closed());
    }

    public function whereItIs(Stack $stack, Session $session): WhatTheWalkSaid
    {
        $this->asked++;

        if (! array_key_exists($this->next, $this->script)) {
            return $this->afterwards;
        }

        return $this->script[$this->next++];
    }

    public function letGo(): WhatTheWalkSaid
    {
        $this->lettingsGo++;

        return WhatTheWalkSaid::closed();
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
