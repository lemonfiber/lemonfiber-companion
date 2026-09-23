<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A settled capability claims a reason and then has none to give.
 *
 * Refused rather than carried, for the reason {@see RemedySaysNothing} gives.
 * *Chosen, because: ▒* asserts that somebody explained themselves and then
 * withholds the explanation, which reads worse than not claiming one — and the
 * core marks that field optional, so there is already a way to say nothing was
 * given. Saying it the other way is the mistake this refuses.
 */
final class ASettlementSaysNothing extends InvalidArgumentException
{
    public static function whereAReasonWasClaimed(): self
    {
        return new self(
            'A capability was settled for a stated reason and the reason was blank, which claims an explanation and withholds it.',
        );
    }
}
