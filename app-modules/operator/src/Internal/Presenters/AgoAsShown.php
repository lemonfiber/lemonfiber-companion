<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;

/**
 * How long ago a reading was taken, carried out of a fold as the key and the
 * count a template needs. A live reading has neither: an empty key and nought.
 */
final readonly class AgoAsShown
{
    private function __construct(public string $said, public int $count) {}

    /** A reading taken now, which says no age. */
    public static function live(): self
    {
        return new self('', 0);
    }

    /** From how long ago it was, counted from the frame's moment. */
    public static function from(HowLongAgo $ago, Instant $taken, Instant $now): self
    {
        return new self($ago->saidOnTheScreen(), $ago->howManySince($taken, $now));
    }
}
