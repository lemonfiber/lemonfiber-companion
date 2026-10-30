<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AServiceWaiting;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\Among;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatThisServiceWaitsForShows;

/**
 * What a service the stack holds back waits for, and the settings among it to change from here.
 */
final readonly class HowWhatAServiceWaitsForReads
{
    /**
     * The service as the settings read names it: what it waits for, and each setting among that, then each setting moved alongside one of them.
     *
     * @param list<string> $alongside the settings a change moves alongside one the service waits for
     */
    public function these(TheServicesSettings $settings, ServiceId $service, array $alongside): WhatThisServiceWaitsForShows
    {
        $waiting = Among::theWaiting($settings->waiting(), $service);

        if (! $waiting instanceof AServiceWaiting) {
            return new WhatThisServiceWaitsForShows(HowTheReadingWent::itCameBack());
        }

        return new WhatThisServiceWaitsForShows(
            HowTheReadingWent::itCameBack(),
            new HowAServiceWaitingReads()->one($waiting),
            new HowASettingIsShown(WhoseASettingIs::theCores())->settings($this->among($settings->settings(), $waiting, $alongside)),
        );
    }

    /** This phone holds no session for the stack. */
    public function signedOut(): WhatThisServiceWaitsForShows
    {
        return new WhatThisServiceWaitsForShows(HowTheReadingWent::theSessionEnded());
    }

    /** What stood in the way of reading the settings. */
    public function met(Obstacle $why): WhatThisServiceWaitsForShows
    {
        return new WhatThisServiceWaitsForShows(HowTheReadingWent::somethingStopped($why));
    }

    /**
     * Each setting the service waits for, in the order the stack lists its settings, then each of these names moved alongside, each once.
     *
     * @param list<string> $alongside
     */
    private function among(TheSettingsRead $settings, AServiceWaiting $waiting, array $alongside): TheSettingsRead
    {
        $found = [];

        foreach ($settings as $setting) {
            if ($waiting->waitsFor($setting)) {
                $found[] = $setting;
            }
        }

        foreach ($alongside as $named) {
            $setting = Among::theSettings($settings, $named);

            if ($setting instanceof ASetting && ! $waiting->waitsFor($setting)) {
                $found[] = $setting;
            }
        }

        return TheSettingsRead::these(...$found);
    }
}
