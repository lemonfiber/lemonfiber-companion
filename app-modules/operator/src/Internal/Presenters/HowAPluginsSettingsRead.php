<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\ThePluginsSettingsAsRead;
use Modules\Operator\Internal\ViewModels\WhatAPluginsSettingsShow;

/**
 * One plugin's settings and secrets, as the template draws them.
 */
final readonly class HowAPluginsSettingsRead
{
    /** Every setting and secret one of the plugin's services reads, and every grant it holds, dated against the frame's moment. */
    public function these(APlugin $plugin, TheServicesSettings $settings, Instant $now): WhatAPluginsSettingsShow
    {
        $its = $settings->readByAnyOf($plugin->services());
        $rows = new HowASettingIsShown(WhoseASettingIs::theNamespaces($plugin->id()));
        $grants = [];

        foreach ($plugin->grants() as $which => $grant) {
            $grants[] = new HowAGrantReads()->one($grant, $which, $now);
        }

        return new WhatAPluginsSettingsShow(
            HowTheReadingWent::itCameBack(),
            new ThePluginsSettingsAsRead($plugin->shown(), $rows->settings($its->settings()), $rows->secrets($its->secrets()), $grants),
        );
    }

    /** The plugin is not among those installed. */
    public function notInstalled(): WhatAPluginsSettingsShow
    {
        return new WhatAPluginsSettingsShow(HowTheReadingWent::itCameBack());
    }

    /** This phone holds no session for the stack. */
    public function signedOut(): WhatAPluginsSettingsShow
    {
        return new WhatAPluginsSettingsShow(HowTheReadingWent::theSessionEnded());
    }

    /** What stood in the way of reading either. */
    public function met(Obstacle $why): WhatAPluginsSettingsShow
    {
        return new WhatAPluginsSettingsShow(HowTheReadingWent::somethingStopped($why));
    }
}
