<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RunningOnASchedule;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheScheduledServices;
use Modules\Kernel\Api\WhatRunsOnASchedule;

/**
 * A stack where a test says how its run-and-exit services last ran, and which remembers being asked.
 */
final class AStackThatSaysWhatRunsOnASchedule implements RunningOnASchedule
{
    /** How many times it was asked. */
    private int $askings = 0;

    /** @param Closure(): WhatRunsOnASchedule $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A stack whose run-and-exit services are these. */
    public static function with(TheScheduledServices $said): self
    {
        return new self(static fn(): WhatRunsOnASchedule => WhatRunsOnASchedule::said($said));
    }

    /** A stack the operator could not reach, for the reason given. */
    public static function met(Obstacle $why): self
    {
        return new self(static fn(): WhatRunsOnASchedule => WhatRunsOnASchedule::met($why));
    }

    /** How many times it was asked. */
    public function askings(): int
    {
        return $this->askings;
    }

    public function scheduled(Stack $stack, Session $session): WhatRunsOnASchedule
    {
        $this->askings++;

        return ($this->answer)();
    }
}
