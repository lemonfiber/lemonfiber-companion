<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Connection\Internal\SettingsKept;
use Modules\Connection\Internal\TheSettingsHeld;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\KnowingThisDevice;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\ThisDevice;

/**
 * The id this install plays under, kept sealed with the phone's settings.
 *
 * Drawn from entropy the first time it is asked for and kept from then on.
 * Clearing what the phone keeps lets it go with the settings, and the next
 * grant is asked for under a new one.
 */
final readonly class ThisDeviceKept implements KnowingThisDevice
{
    private TheSettingsHeld $settings;

    public function __construct(Sealed $seal, SettingsKept $kept, private Entropy $entropy, Clock $clock)
    {
        $this->settings = new TheSettingsHeld($seal, $kept, $clock);
    }

    public function thisDevice(): ThisDevice
    {
        $settings = $this->settings->current();

        if ($settings->device instanceof ThisDevice) {
            return $settings->device;
        }

        $drawn = ThisDevice::drawnFrom($this->entropy->nonce());
        $this->settings->keep($settings->playingAs($drawn));

        return $drawn;
    }
}
