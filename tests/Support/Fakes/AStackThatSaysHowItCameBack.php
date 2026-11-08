<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\AskingHowItCameBack;
use Modules\Kernel\Api\HowItCameBack;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatTheLastStartCameTo;

/**
 * A stack where a test says what its last start came to, and which remembers being asked.
 *
 * Not `readonly`: what was asked is written when the asking happens.
 */
final class AStackThatSaysHowItCameBack implements AskingHowItCameBack
{
    /** How many times it was asked, which is how a screen asking twice a frame is caught. */
    private int $askings = 0;

    /** @param Closure(): HowItCameBack $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack whose last start came to this. */
    public static function with(WhatTheLastStartCameTo $said): self
    {
        return new self(static fn(): HowItCameBack => HowItCameBack::said($said));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): HowItCameBack => HowItCameBack::met($why));
    }

    /** How many times it was asked. */
    public function askings(): int
    {
        return $this->askings;
    }

    public function afterTheLastStart(Stack $stack, Session $session): HowItCameBack
    {
        $this->askings++;

        return ($this->answer)();
    }
}
