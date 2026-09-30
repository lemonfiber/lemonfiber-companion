<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Lemonfiber\Native\Clock;
use Modules\Kernel\Api\LocalZone;
use Modules\Kernel\Api\Zone;

/**
 * The zone the phone's clock is set to, reached through lemonfiber's own native expansion.
 *
 * Thin on purpose: the platform names the zone and {@see Zone} decides what a
 * name it cannot place comes to. Where nothing answered — every machine that
 * is not a handset — the clock is read as UTC, which is the zone PHP itself is
 * configured with here.
 */
final readonly class PlatformZone implements LocalZone
{
    public function __construct(private Clock $clock) {}

    public function zone(): Zone
    {
        $named = $this->clock->zone();

        return $named === '' ? Zone::utc() : Zone::named($named);
    }
}
