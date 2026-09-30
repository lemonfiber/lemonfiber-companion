<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\DeviceAuth;
use Modules\Kernel\Api\Lock;
use Modules\Kernel\Api\Stacks;

/**
 * Whether a screen may be built, asked before every one is.
 *
 * The device holds the lock and says whether it stands. What it cannot know is
 * whether there is anything behind it, and that is the one question asked here:
 * a lock over a device that holds no pairing guards nothing, and the first run
 * reaches pairing without a prompt.
 *
 * **Read from the store itself, and only while the lock stands.** Not a flag
 * this app keeps, which could outlive the pairing it was about; and not on every
 * screen, because once the lock is open there is nothing to ask. A store that
 * cannot be read counts as holding something — {@see Stacks::holdsAny()} says
 * why — so an unreadable store never waives the lock.
 */
final readonly class TheLock
{
    public function __construct(private DeviceAuth $device, private Stacks $stacks) {}

    /** How the lock stands, waived first where the store holds nothing it guards. */
    public function standing(): Lock
    {
        $standing = $this->device->standing();

        return $standing->either(
            held: fn(): Lock => $this->stacks->holdsAny() ? $standing : $this->device->waive(),
            open: static fn(): Lock => $standing,
        );
    }
}
