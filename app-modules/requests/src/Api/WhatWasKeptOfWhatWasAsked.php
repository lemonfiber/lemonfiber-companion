<?php

declare(strict_types=1);

namespace Modules\Requests\Api;

use Closure;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Requested;
use Modules\Requests\Internal\RequestsAsOf;

/**
 * What the household asked a stack for, as the phone kept it from an earlier session, or that it kept none.
 *
 * A kept reading always carries when it was read and the moment it was handed
 * back, so a screen drawing one says how old it is without a clock of its own;
 * there is no arm that holds a reading without them.
 */
final readonly class WhatWasKeptOfWhatWasAsked
{
    private function __construct(private ?RequestsAsOf $kept) {}

    /** A reading kept from when it was read, handed back now. */
    public static function readAt(Requested $requested, Instant $readAt, Instant $now): self
    {
        return new self(new RequestsAsOf($requested, $readAt, $now));
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
     * @param Closure(Requested, Instant, Instant): TKept $kept    the reading, when it was read, and now
     * @param Closure(): TNothing                       $nothing
     *
     * @return TKept|TNothing
     */
    public function either(Closure $kept, Closure $nothing): object
    {
        return $this->kept instanceof RequestsAsOf ? $kept($this->kept->requested, $this->kept->readAt, $this->kept->now) : $nothing();
    }
}
