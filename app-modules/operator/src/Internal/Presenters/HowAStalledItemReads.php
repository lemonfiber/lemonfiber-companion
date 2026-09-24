<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\Stuck;
use Modules\Operator\Internal\ViewModels\WhatOneStalledItemSays;

/**
 * One thing whose download stopped, as the row a screen lists it on.
 *
 * `F2` — the stalled item is the only argument, so a row is read in a test by
 * stating one stopped download and nothing else.
 */
final readonly class HowAStalledItemReads
{
    /**
     * Fold one stalled item into the fields a row needs.
     *
     * `stated()` is answered here rather than in the screen, so a screen
     * listing what stopped is a loop over this and not a fold per row — and the
     * one closure builds the whole row, which is what keeps a title from
     * reaching a template without the two facts that make it actionable.
     */
    public function in(Stuck $stuck): WhatOneStalledItemSays
    {
        return $stuck->stated(
            static fn(
                string $title,
                ServiceId $service,
                Stage $stage,
            ): WhatOneStalledItemSays => new WhatOneStalledItemSays(
                title: $title,
                service: $service->named(),
                stage: $stage->shown(),
                stageSaid: $stage->saidOnTheScreen(),
                stillMoving: $stage->stillMoving(),
            ),
        );
    }
}
