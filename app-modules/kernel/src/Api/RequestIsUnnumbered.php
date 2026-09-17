<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A request arrived without a number a stack could be asked about it by.
 *
 * Refused where the value is built rather than carried to a caller, for
 * {@see RequestWasRefusedForNothing}'s reason: this is a value that cannot be
 * constructed, so there is nothing for a caller to open (`C1`, `C3`).
 *
 * A stack numbers what it holds from one. Zero is what a missing field reads as
 * and what an empty form field sends, and `N2-R11`'s decision is one where
 * acting on the wrong subject is the whole harm — so it is refused rather than
 * sent and found out about afterwards.
 */
final class RequestIsUnnumbered extends InvalidArgumentException
{
    public static function atNumber(int $number): self
    {
        return new self(sprintf(
            'A request is numbered from one and this one is numbered %d, so there is nothing to ask a stack about.',
            $number,
        ));
    }
}
