<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;

/**
 * How long ago a reading was taken, carried out of a fold as the key and the
 * count a template needs. A live reading has neither: an empty key and nought.
 * An undated one has the key saying the clock would not tell, and nought.
 */
final readonly class AgoAsShown
{
    private function __construct(public string $said, public int $count) {}

    /** No age: a reading taken now, or a moment nobody said or said in a form no clock reads. */
    public static function saysNothing(): self
    {
        return new self('', 0);
    }

    /** A change the stack's clock would not date, which says so rather than an age. */
    public static function undated(): self
    {
        return new self(HowTheRecordReads::CLOCK_UNREADABLE, 0);
    }

    /** From how long ago it was, counted from the frame's moment. */
    public static function from(HowLongAgo $ago, Instant $taken, Instant $now): self
    {
        return new self($ago->saidOnTheScreen(), $ago->howManySince($taken, $now));
    }
}
