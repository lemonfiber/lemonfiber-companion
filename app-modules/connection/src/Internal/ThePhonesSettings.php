<?php

declare(strict_types=1);

namespace Modules\Connection\Internal;

use Modules\Kernel\Api\HowLongReadingsAreKept;
use Modules\Kernel\Api\LockAfter;
use Modules\Kernel\Api\ThisDevice;

/**
 * Every setting the phone keeps, as one value: each is kept, or at its standard.
 *
 * With them, the id this install plays under, which has no standard: it is
 * drawn the first time it is asked for.
 */
final readonly class ThePhonesSettings
{
    public function __construct(public LockAfter $lockAfter, public HowLongReadingsAreKept $readingsKept, public ?ThisDevice $device) {}

    /** Every setting at its standard, which is the phone before the operator chooses anything. */
    public static function standard(): self
    {
        return new self(LockAfter::standard(), HowLongReadingsAreKept::standard(), null);
    }

    /** These settings, with the lock's time away changed. */
    public function lockingAfter(LockAfter $after): self
    {
        return new self($after, $this->readingsKept, $this->device);
    }

    /** These settings, with how long readings are kept changed. */
    public function keepingReadingsFor(HowLongReadingsAreKept $kept): self
    {
        return new self($this->lockAfter, $kept, $this->device);
    }

    /** These settings, with the id this install plays under. */
    public function playingAs(ThisDevice $device): self
    {
        return new self($this->lockAfter, $this->readingsKept, $device);
    }
}
