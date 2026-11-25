<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheCopySettings;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\ViewModels\HowCopiesRunShows;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;

/**
 * How the stack's two settings for a running copy read: each with its value where the operator set one, or that it is at the stack's default, with that default's value where the stack sends one.
 */
final readonly class HowTheCopySettingsRead
{
    /** The two, out of everything the stack lists. */
    public function these(TheServicesSettings $settings): HowCopiesRunShows
    {
        return new HowCopiesRunShows(
            HowTheReadingWent::itCameBack(),
            new HowASettingIsShown(WhoseASettingIs::theCores())->settings(TheCopySettings::in($settings->settings())),
        );
    }

    /** This phone holds no session for the stack. */
    public function signedOut(): HowCopiesRunShows
    {
        return new HowCopiesRunShows(HowTheReadingWent::theSessionEnded());
    }

    /** What stood in the way of reading the settings. */
    public function met(Obstacle $why): HowCopiesRunShows
    {
        return new HowCopiesRunShows(HowTheReadingWent::somethingStopped($why));
    }
}
