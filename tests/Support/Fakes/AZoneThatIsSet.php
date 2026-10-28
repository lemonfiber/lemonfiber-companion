<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\LocalZone;
use Modules\Kernel\Api\Zone;

/**
 * A phone whose clock is set to whichever zone the test named.
 *
 * Held to the same contract as the platform's in `LocalZoneContractTest`.
 */
final class AZoneThatIsSet implements LocalZone
{
    private function __construct(private Zone $zone) {}

    /** A phone set to the zone of that name. */
    public static function to(string $name): self
    {
        return new self(Zone::named($name));
    }

    /** The phone is set to the zone of that name from now on. */
    public function fromNowOnSetTo(string $name): void
    {
        $this->zone = Zone::named($name);
    }

    public function zone(): Zone
    {
        return $this->zone;
    }
}
