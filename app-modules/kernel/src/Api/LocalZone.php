<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Which time zone this phone's clock is set to, asked rather than taken.
 *
 * {@see Clock}'s sibling. The moment is the same everywhere; what a clock on
 * the wall reads at it is not, and a screen that shows a time to somebody
 * holding the phone owes them the one on their own clock. The platform knows
 * the zone and PHP does not, so it arrives through a port like every other
 * thing only the platform knows.
 */
interface LocalZone
{
    /** The zone the phone's clock is set to right now. */
    public function zone(): Zone;
}
