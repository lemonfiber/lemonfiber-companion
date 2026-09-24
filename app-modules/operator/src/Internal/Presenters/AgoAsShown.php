<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;

/** How long ago a reading was taken, carried out of a fold as the key and the count a template needs. */
final readonly class AgoAsShown
{
    private function __construct(public string $said, public int $count) {}

    /** From how long ago it was, counted from the frame's moment. */
    public static function from(HowLongAgo $ago, Instant $taken, Instant $now): self
    {
        return new self($ago->saidOnTheScreen(), $ago->howManySince($taken, $now));
    }
}
