<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Whether taking lemonfiber off lets what is still coming down land first.
 *
 * Two answers, and neither is chosen for the operator: stopping interrupts
 * whatever is still arriving, and waiting lets it finish before anything
 * stops.
 */
enum WhetherToWait: string
{
    /** Let what is still coming down land, then remove. */
    case ForTheDownloads = 'for_the_downloads';

    /** Go ahead now, interrupting what is still coming down. */
    case GoAheadNow = 'go_ahead_now';

    /** Whether the stack is asked to wait, which is the one thing the wire carries. */
    public function waits(): bool
    {
        return $this === self::ForTheDownloads;
    }
}
