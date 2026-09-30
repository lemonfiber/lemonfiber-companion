<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Connection\Internal\SettingsKept;
use Modules\Connection\Internal\TheSettingsAsKept;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\DeviceAuth;
use Modules\Kernel\Api\Lock;
use Modules\Kernel\Api\LockAfter;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Unsealed;

/**
 * How long the app may be away before the lock asks again, as the operator chose.
 *
 * Kept sealed, like everything the phone keeps, and told to the device, which
 * measures the time away. Until the operator chooses it is immediately, and a
 * phone that can keep nothing forgets the choice when the app closes.
 */
final readonly class LockingAfter
{
    public function __construct(
        private Sealed $seal,
        private SettingsKept $kept,
        private DeviceAuth $device,
        private Clock $clock,
    ) {}

    /** What the operator chose, or immediately. */
    public function current(): LockAfter
    {
        return $this->kept->kept()->either(
            found: fn(SealedPayload $payload, Shape $shape): LockAfter => $this->seal->open($payload)->either(
                opened: static fn(Unsealed $value): LockAfter => TheSettingsAsKept::read($shape, $value),
                unreadable: static fn(): LockAfter => LockAfter::standard(),
            ),
            none: static fn(): LockAfter => LockAfter::standard(),
        );
    }

    /** Keep the operator's choice, tell the device, and answer what is now in force. */
    public function choose(LockAfter $after): LockAfter
    {
        $this->seal->seal(TheSettingsAsKept::written($after))->either(
            sealed: fn(SealedPayload $payload): Noted => $this->kept->keep($payload, Shape::current(), $this->clock->now()),
            refused: static fn(): Noted => Noted::notKept(),
        );

        $this->device->allowAway($after->howLong());

        return $after;
    }

    /** Tell the device what the operator chose, as the app opens past the lock. */
    public function toldTheDevice(): Lock
    {
        return $this->device->allowAway($this->current()->howLong());
    }
}
