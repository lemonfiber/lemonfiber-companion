<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Whom a grant was answered for: the member whose session asked, on this device.
 *
 * Kept beside the grant and compared before it is used. A grant plays as the
 * member it was opened for, under their limits, so one kept for somebody else
 * on the same stack, or under a device id this install no longer goes by, is
 * no grant for whoever is asking now.
 */
final readonly class TheGrantIsFor
{
    private function __construct(private Whose $member, private ThisDevice $device) {}

    public static function of(Whose $member, ThisDevice $device): self
    {
        return new self($member, $device);
    }

    /** Whether this is the same member on the same device. */
    public function is(self $other): bool
    {
        return $this->member->is($other->member) && $this->device->shown() === $other->device->shown();
    }

    /** The member, as the one string a store keeps it as. */
    public function memberForTheStore(): string
    {
        return $this->member->forTheStore();
    }

    /** The device's id, as a store keeps it. */
    public function deviceForTheStore(): string
    {
        return $this->device->shown();
    }
}
