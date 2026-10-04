<?php

declare(strict_types=1);

namespace Modules\Services\Api;

use Closure;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Instant;
use Modules\Services\Internal\AListingAsOf;

/**
 * The listing of what a stack runs that the phone kept from an earlier session, or that it kept none.
 *
 * A kept listing always carries when it was read and the moment it was handed
 * back, so a screen drawing one says how old it is without a clock of its own;
 * there is no arm that holds a listing without them.
 */
final readonly class WhatWasKeptOfWhatItRuns
{
    private function __construct(private ?AListingAsOf $kept) {}

    /** A listing kept from when it was read, handed back now. */
    public static function readAt(Daemons $daemons, Instant $readAt, Instant $now): self
    {
        return new self(new AListingAsOf($daemons, $readAt, $now));
    }

    /** Nothing kept for the stack, or nothing that still reads. */
    public static function nothing(): self
    {
        return new self(null);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TKept of object
     * @template TNothing of object
     *
     * @param Closure(Daemons, Instant, Instant): TKept $kept    the listing, when it was read, and now
     * @param Closure(): TNothing                       $nothing
     *
     * @return TKept|TNothing
     */
    public function either(Closure $kept, Closure $nothing): object
    {
        return $this->kept instanceof AListingAsOf ? $kept($this->kept->daemons, $this->kept->readAt, $this->kept->now) : $nothing();
    }
}
