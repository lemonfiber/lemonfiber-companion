<?php

declare(strict_types=1);

namespace Modules\Updates\Api;

use Closure;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Upkeep;
use Modules\Updates\Internal\AnUpkeepAsOf;

/**
 * The reading of where a stack stands that the phone kept from an earlier session, or that it kept none.
 *
 * A kept reading always carries when it was read, because a screen drawing
 * one says how old it is; there is no arm that holds a reading without it.
 */
final readonly class WhatWasKeptOfTheUpkeep
{
    private function __construct(private ?AnUpkeepAsOf $kept) {}

    /** A reading kept from when it was read. */
    public static function readAt(Upkeep $upkeep, Instant $readAt): self
    {
        return new self(new AnUpkeepAsOf($upkeep, $readAt));
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
     * @param Closure(Upkeep, Instant): TKept $kept
     * @param Closure(): TNothing             $nothing
     *
     * @return TKept|TNothing
     */
    public function either(Closure $kept, Closure $nothing): object
    {
        return $this->kept instanceof AnUpkeepAsOf ? $kept($this->kept->upkeep, $this->kept->readAt) : $nothing();
    }
}
