<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function count;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Settings;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatThisStackIsSetToTurnedOutToBe;

/**
 * The settings listing, turned into what the screen draws.
 *
 * Shaped after {@see HowAStallReads}: three ways a reading ends, each with its
 * own named method, so a screen cannot reach a fourth by leaving a branch out.
 */
final readonly class HowTheSettingsRead
{
    public function signedOut(): WhatThisStackIsSetToTurnedOutToBe
    {
        return new WhatThisStackIsSetToTurnedOutToBe(
            went: HowTheReadingWent::theSessionEnded(),
            set: [],
            howMany: 0,
        );
    }

    public function these(Settings $set): WhatThisStackIsSetToTurnedOutToBe
    {
        $rows = [];

        foreach ($set as $one) {
            $rows[] = new HowASettingReads()->in($one);
        }

        // Counted off the listing rather than off `$rows`, so how many the
        // screen says there are is decided where the stack said it. A fold
        // that dropped a row would then disagree with the count beside it
        // rather than presenting a short listing as a whole one.
        return new WhatThisStackIsSetToTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            set: $rows,
            howMany: count($set),
        );
    }

    public function met(Obstacle $why): WhatThisStackIsSetToTurnedOutToBe
    {
        return new WhatThisStackIsSetToTurnedOutToBe(
            went: HowTheReadingWent::somethingStopped($why),
            set: [],
            howMany: 0,
        );
    }
}
