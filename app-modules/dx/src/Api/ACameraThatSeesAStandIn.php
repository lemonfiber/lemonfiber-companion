<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use Closure;
use Modules\Dx\Internal\WhatAPairingCodeWouldSay;
use Modules\Kernel\Api\Scanning;
use Modules\Kernel\Api\WhatTheCameraSaw;

/**
 * A camera that always sees a code for a machine nobody has met.
 *
 * The first run was the one sequence this module could be walked *to* and not
 * *through*. The sequence ends at pairing, a paired device never sees
 * the sequence again, and {@see ADeviceAlreadyPaired} seeds three machines — so
 * looking at the first run meant turning that affordance off, and with it off
 * there was no code to pair with either. The screen an operator meets first was
 * the screen this module could not reach.
 *
 * **There is no camera on a laptop, and there is no stack in front of the
 * phone.** Either alone makes the scanned road unwalkable, which is why this
 * stands in for the port rather than for the platform: what runs is the real
 * screen, the real reader and the real refusals — `Pairing::read()` does its own
 * refusing of the material this hands it, and the material is written from the
 * enum that defines the format rather than from a copy of it.
 *
 * **It sees the same code every time, and that is the point.** A run that
 * scanned something different each time would pair a new machine on every
 * attempt, which is a device filling up with stand-ins; seeing one means the
 * second scan is `Configured::with()`'s re-pairing rule, which is a thing worth
 * looking at and had nowhere to be looked at.
 *
 * @implements StandsIn<Scanning>
 */
final readonly class ACameraThatSeesAStandIn implements StandsIn
{
    public function insteadOf(): string
    {
        return Scanning::class;
    }

    public function which(): Scanning
    {
        return new class implements Scanning {
            public function forAPairingCode(Closure $saw): void
            {
                $saw(WhatTheCameraSaw::read(WhatAPairingCodeWouldSay::asItWouldBeScanned()));
            }
        };
    }
}
