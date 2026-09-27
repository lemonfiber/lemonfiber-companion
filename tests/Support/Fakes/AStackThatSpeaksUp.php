<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function array_values;

use Modules\Kernel\Api\Hearing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatWasHeard;

/**
 * A stack's event stream, as a script of what each wake of a screen hears.
 *
 * Two endings, because a real stream has two. One held open goes quiet once it
 * has said its piece, which is what a stack that is working does between
 * gathers. One that ends says so once and then waits to be opened again,
 * which is what the adapter answers once a stream closes under it.
 *
 * It counts how often it was asked and let go of, because a screen's promise
 * is about both: asked only on its cadence, and let go of whenever nobody can
 * see it.
 */
final class AStackThatSpeaksUp implements Hearing
{
    private int $next = 0;

    private int $asked = 0;

    private int $lettingsGo = 0;

    /** @param list<WhatWasHeard> $script */
    private function __construct(
        private array $script,
        private readonly WhatWasHeard $afterwards,
    ) {}

    /** A stream that says these in turn and then stays open, saying nothing. */
    public static function holdingOpen(WhatWasHeard ...$said): self
    {
        return new self(array_values($said), WhatWasHeard::nothing());
    }

    /** A stream that says these in turn and then is closed. */
    public static function thenEnding(WhatWasHeard ...$said): self
    {
        return new self(array_values($said), WhatWasHeard::closed());
    }

    public function howItIs(Stack $stack, Session $session): WhatWasHeard
    {
        $this->asked++;

        if (! array_key_exists($this->next, $this->script)) {
            return $this->afterwards;
        }

        return $this->script[$this->next++];
    }

    public function letGo(): WhatWasHeard
    {
        $this->lettingsGo++;

        return WhatWasHeard::closed();
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
