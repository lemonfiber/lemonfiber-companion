<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\HowAServiceTookIt;
use Modules\Operator\Internal\ViewModels\WhatOneServiceTookItSays;

/**
 * What one service made of an update, as the row a screen lists it on.
 *
 * The sibling of {@see HowAReleaseReads} and written the same way: Blade has no
 * `either()` and cannot be given one, so the folding happens here and the
 * template reads fields.
 */
final readonly class HowATakenUpdateReads
{
    public function of(HowAServiceTookIt $took): WhatOneServiceTookItSays
    {
        return new WhatOneServiceTookItSays(
            service: $took->service()->named(),
            endingSaid: $took->ending()->saidOnTheScreen(),
            undoSaid: $took->undo()->saidOnTheScreen(),
            arrived: $took->ending()->arrived(),
            undoCarriesTheDataWithIt: $took->undo()->carriesTheDataWithIt(),
        );
    }
}
