<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\HowManyLines;

/**
 * How many log lines from each service a support bundle may take: the three
 * windows the screen offers, fewest first.
 *
 * A closed set, because the screen offers these and no others; backed by the
 * count, because that is what the operator reads on a chip and what a tap
 * hands back.
 */
enum ALogWindow: int
{
    /** Enough for a fault that is happening now. */
    case Fewer = 50;

    /** As much as a phone shows of one service, which is where a bundle starts. */
    case AsOnAPhone = HowManyLines::ON_A_PHONE;

    /** Enough for a fault that came and went. */
    case More = 1_000;

    /** The window as the lines a bundle asks each service for. */
    public function lines(): HowManyLines
    {
        return HowManyLines::of($this->value);
    }
}
