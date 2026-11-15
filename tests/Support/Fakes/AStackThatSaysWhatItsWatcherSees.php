<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\AskingTheWatcher;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheWatcherSaid;
use Modules\Kernel\Api\WhatTheWatcherSees;

/**
 * A stack where a test says what its outside watcher sees, and which remembers being asked.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatSaysWhatItsWatcherSees implements AskingTheWatcher
{
    /** How many times it was asked, which is how a screen asking twice a frame is caught. */
    private int $askings = 0;

    /** @param Closure(): WhatTheWatcherSaid $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack whose watcher sees this. */
    public static function with(WhatTheWatcherSees $said): self
    {
        return new self(static fn(): WhatTheWatcherSaid => WhatTheWatcherSaid::said($said));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatTheWatcherSaid => WhatTheWatcherSaid::met($why));
    }

    /** How many times it was asked. */
    public function askings(): int
    {
        return $this->askings;
    }

    public function whatItSees(Stack $stack, Session $session): WhatTheWatcherSaid
    {
        $this->askings++;

        return ($this->answer)();
    }
}
