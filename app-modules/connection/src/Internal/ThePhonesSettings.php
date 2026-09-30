<?php

declare(strict_types=1);

namespace Modules\Connection\Internal;

use Modules\Kernel\Api\HowLongReadingsAreKept;
use Modules\Kernel\Api\LockAfter;

/** Every setting the phone keeps, as one value: each is kept, or at its standard. */
final readonly class ThePhonesSettings
{
    public function __construct(public LockAfter $lockAfter, public HowLongReadingsAreKept $readingsKept) {}

    /** Every setting at its standard, which is the phone before the operator chooses anything. */
    public static function standard(): self
    {
        return new self(LockAfter::standard(), HowLongReadingsAreKept::standard());
    }

    /** These settings, with the lock's time away changed. */
    public function lockingAfter(LockAfter $after): self
    {
        return new self($after, $this->readingsKept);
    }

    /** These settings, with how long readings are kept changed. */
    public function keepingReadingsFor(HowLongReadingsAreKept $kept): self
    {
        return new self($this->lockAfter, $kept);
    }
}
