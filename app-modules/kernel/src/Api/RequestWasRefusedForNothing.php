<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A request was declined and no reason came with it.
 *
 * Refused where the values become a {@see TurnedDown}, for
 * {@see RepairSaysNothing}'s reason: this is a value that cannot be constructed
 * rather than a refusal crossing a boundary, so there is nothing for a caller to
 * open (`C1`, `C3`).
 *
 * The reason is part of declining rather than something beside it, so
 * this is a stack that broke the rule rather than a row to render short — and
 * rendering it short is the worst available answer, because *declined* with no
 * reason is exactly the screen that sends somebody to ask their operator in
 * person.
 */
final class RequestWasRefusedForNothing extends InvalidArgumentException
{
    public static function andSomebodyIsWaitingToHearWhy(): self
    {
        return new self(
            'A request was declined with no reason given, and D7-R7 makes the reason part of declining rather than an extra beside it. Somebody in the house is waiting to be told why.',
        );
    }
}
