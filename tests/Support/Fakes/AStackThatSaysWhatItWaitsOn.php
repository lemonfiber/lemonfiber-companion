<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function array_values;

use Modules\Kernel\Api\HearingTheStart;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatAStartWaitsOn;

/**
 * A stack's event stream while a start runs, as a script of what each wake of a screen hears.
 *
 * It says these in turn and then nothing new, which is what the adapter answers
 * once the stack has said its last line.
 *
 * It counts how often it was asked and let go of, because a screen's promise
 * is about both: asked only while a start sent from it runs, and let go of
 * once the start is over and whenever the screen stops.
 */
final class AStackThatSaysWhatItWaitsOn implements HearingTheStart
{
    private int $next = 0;

    private int $asked = 0;

    private int $lettingsGo = 0;

    /** @param list<WhatAStartWaitsOn> $script */
    private function __construct(private readonly array $script) {}

    /** A stream that says these about a running start in turn, and then nothing new. */
    public static function saying(WhatAStartWaitsOn ...$said): self
    {
        return new self(array_values($said));
    }

    public function whatItWaitsOn(Stack $stack, Session $session): WhatAStartWaitsOn
    {
        $this->asked++;

        if (! array_key_exists($this->next, $this->script)) {
            return WhatAStartWaitsOn::nothingNew();
        }

        return $this->script[$this->next++];
    }

    public function letGo(): WhatAStartWaitsOn
    {
        $this->lettingsGo++;

        return WhatAStartWaitsOn::nothingNew();
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
