<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use function is_string;

use Modules\Household\Internal\ViewModels\WhatOnePosterSays;
use Modules\Household\Internal\ViewModels\WhatThisTitleTurnedOutToBe;
use Modules\Kernel\Api\Medium;

/**
 * One title's screen, from what the poster that opened it handed over.
 *
 * Pure. What it is handed is what the shelf said, carried across the
 * navigation as words; a name that is missing or empty, or a kind this app
 * has no word for, is a title it cannot draw, and it says so rather than
 * drawing a title with a part missing.
 */
final readonly class HowATitleReads
{
    /** What was handed over: the name, the kind as the core spells it, and the year or nothing. */
    public function handed(mixed $titled, mixed $medium, mixed $year): WhatThisTitleTurnedOutToBe
    {
        $kind = is_string($medium) ? Medium::tryFrom($medium) : null;

        if (! is_string($titled) || $titled === '' || ! $kind instanceof Medium) {
            return WhatThisTitleTurnedOutToBe::notHandedOver();
        }

        return WhatThisTitleTurnedOutToBe::as(
            WhatOnePosterSays::ofATitle($titled, $kind->saidOnTheScreen(), is_string($year) ? $year : ''),
        );
    }
}
