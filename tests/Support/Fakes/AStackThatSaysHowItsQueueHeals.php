<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\AskingTheQueue;
use Modules\Kernel\Api\HowTheQueueHeals;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheQueueSaid;

/**
 * A stack where a test says how its queue heals itself, and which remembers being asked.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatSaysHowItsQueueHeals implements AskingTheQueue
{
    /** How many times it was asked, which is how a screen asking twice a frame is caught. */
    private int $askings = 0;

    /** @param Closure(): WhatTheQueueSaid $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack whose queue heals itself like this. */
    public static function with(HowTheQueueHeals $said): self
    {
        return new self(static fn(): WhatTheQueueSaid => WhatTheQueueSaid::said($said));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatTheQueueSaid => WhatTheQueueSaid::met($why));
    }

    /** How many times it was asked. */
    public function askings(): int
    {
        return $this->askings;
    }

    public function howItHeals(Stack $stack, Session $session): WhatTheQueueSaid
    {
        $this->askings++;

        return ($this->answer)();
    }
}
