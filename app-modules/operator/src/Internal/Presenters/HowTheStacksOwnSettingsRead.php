<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Operator\Internal\ViewModels\AnOwnersSettingsAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatTheStackItselfReadsShows;

/**
 * The settings and secrets no installed plugin's services read, grouped by whose they are, as the template draws them.
 */
final readonly class HowTheStacksOwnSettingsRead
{
    /** Every setting and secret none of these services reads, the core's first and then each namespace's in the order the stack lists them. */
    public function these(TheServicesSettings $settings, Services $thePluginsServices): WhatTheStackItselfReadsShows
    {
        $its = $settings->readByNoneOf($thePluginsServices);
        $shown = [];

        foreach ($its->owners() as $whose) {
            $rows = new HowASettingIsShown($whose);
            $owned = $its->ownedBy($whose);
            $shown[] = new AnOwnersSettingsAsShown(HowASettingIsShown::whose($whose), $rows->settings($owned->settings()), $rows->secrets($owned->secrets()));
        }

        return new WhatTheStackItselfReadsShows(HowTheReadingWent::itCameBack(), $shown);
    }

    /** The plugins were read on this frame, and the settings wait for the next. */
    public function waits(): WhatTheStackItselfReadsShows
    {
        return new WhatTheStackItselfReadsShows(HowTheReadingWent::itCameBack(), waits: true);
    }

    /** This phone holds no session for the stack. */
    public function signedOut(): WhatTheStackItselfReadsShows
    {
        return new WhatTheStackItselfReadsShows(HowTheReadingWent::theSessionEnded());
    }

    /** What stood in the way of reading the plugins or the settings. */
    public function met(Obstacle $why): WhatTheStackItselfReadsShows
    {
        return new WhatTheStackItselfReadsShows(HowTheReadingWent::somethingStopped($why));
    }
}
