<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Connection\Internal\SettingsKept;
use Modules\Connection\Internal\TheSettingsHeld;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\HowLongReadingsAreKept;
use Modules\Kernel\Api\KeepsReadingsFor;
use Modules\Kernel\Api\Sealed;

/**
 * How long readings are kept, as one of the phone's settings.
 *
 * Kept sealed beside the lock's time away, in the one row the phone's
 * settings are kept in. A phone that can keep nothing holds the choice for as
 * long as the app is open.
 */
final readonly class KeepingReadingsFor implements KeepsReadingsFor
{
    private TheSettingsHeld $settings;

    public function __construct(Sealed $seal, SettingsKept $kept, Clock $clock)
    {
        $this->settings = new TheSettingsHeld($seal, $kept, $clock);
    }

    public function keptFor(): HowLongReadingsAreKept
    {
        return $this->settings->current()->readingsKept;
    }

    public function keepFor(HowLongReadingsAreKept $kept): HowLongReadingsAreKept
    {
        $this->settings->keep($this->settings->current()->keepingReadingsFor($kept));

        return $kept;
    }
}
