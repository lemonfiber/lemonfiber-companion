<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

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
        return new WhatThisStackIsSetToTurnedOutToBe(HowTheReadingWent::theSessionEnded());
    }

    public function these(Settings $set): WhatThisStackIsSetToTurnedOutToBe
    {
        $rows = [];

        foreach ($set as $one) {
            $rows[] = new HowASettingReads()->in($one);
        }

        return new WhatThisStackIsSetToTurnedOutToBe(HowTheReadingWent::itCameBack(), $rows);
    }

    public function met(Obstacle $why): WhatThisStackIsSetToTurnedOutToBe
    {
        return new WhatThisStackIsSetToTurnedOutToBe(HowTheReadingWent::somethingStopped($why));
    }
}
