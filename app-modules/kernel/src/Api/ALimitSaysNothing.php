<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * The stack named something it cannot act on and did not say what, or why.
 *
 * Refused rather than carried, for the reason {@see RemedySaysNothing} gives.
 * Both halves are the whole of this value: *there is something here lemonfiber
 * cannot help with* is not an answer an operator can act on, and neither is a
 * reason attached to nothing. Either half missing makes the other unreadable.
 */
final class ALimitSaysNothing extends InvalidArgumentException
{
    public static function aboutWhat(): self
    {
        return new self(
            'The stack said it could not act on something and did not say what, so nothing could be shown about which.',
        );
    }

    public static function why(): self
    {
        return new self(
            'The stack named something it cannot act on and gave no reason, and a limit without one reads as a fault.',
        );
    }
}
