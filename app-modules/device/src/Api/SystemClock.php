<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Instant;

use function time;

/**
 * The platform's own clock, which is the one thing this class knows.
 *
 * `time()` is called in this module and `vault` and nowhere else. Somewhere has
 * to read the real clock, and an adapter is the layer whose job is knowing one
 * outside thing.
 *
 * It has no state and no configuration. A clock with a timezone would be a
 * clock making a rendering decision, and `Instant` is deliberately a count of
 * seconds so that decision stays on the screen that is drawing.
 */
final readonly class SystemClock implements Clock
{
    public function now(): Instant
    {
        return Instant::atEpochSeconds(time());
    }
}
