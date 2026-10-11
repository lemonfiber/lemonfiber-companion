<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function array_values;

use Modules\Kernel\Api\ALineItSaid;
use Modules\Kernel\Api\HearingTheWalk;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatAStreamSaid;

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

    /**
     * @param list<WhatAStreamSaid<ALineItSaid>> $script
     *
     * @param WhatAStreamSaid<ALineItSaid> $afterwards
     */
    private function __construct(
        private array $script,
        private readonly WhatAStreamSaid $afterwards,
    ) {}

    /**
     * A stream that says these in turn and then stays open, saying nothing.
     *
     * @param WhatAStreamSaid<ALineItSaid> ...$said
     */
    public static function holdingOpen(WhatAStreamSaid ...$said): self
    {
        return new self(array_values($said), WhatAStreamSaid::nothing());
    }

    /**
     * A stream that says these in turn and then is closed.
     *
     * @param WhatAStreamSaid<ALineItSaid> ...$said
     */
    public static function thenEnding(WhatAStreamSaid ...$said): self
    {
        return new self(array_values($said), WhatAStreamSaid::closed());
    }

    /** @return WhatAStreamSaid<ALineItSaid> */
    public function whereItIs(Stack $stack, Session $session): WhatAStreamSaid
    {
        $this->asked++;

        if (! array_key_exists($this->next, $this->script)) {
            return $this->afterwards;
        }

        return $this->script[$this->next++];
    }

    /** @return WhatAStreamSaid<ALineItSaid> */
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
