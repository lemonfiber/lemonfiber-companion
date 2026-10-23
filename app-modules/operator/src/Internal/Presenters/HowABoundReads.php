<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ACheckThatDisturbs;
use Modules\Operator\Internal\ViewModels\WhatAVerbTakesAwaySays;

/**
 * How long a check that disturbs disturbs for, as the stack bounds it, or that it gives no bound.
 *
 * The figure is the stack's, and where it gives none the line says so rather
 * than putting one of this app's in its place.
 */
final readonly class HowABoundReads
{
    /** The line saying the stack's bound, or that it gave none. */
    public const string NO_BOUND = 'health.disturbs.no_bound';

    /** The line saying how long at most. */
    public const string AT_MOST = 'health.for_at_most';

    public function of(ACheckThatDisturbs $disturbing): WhatAVerbTakesAwaySays
    {
        return $disturbing->either(
            bounded: static fn(int $seconds): WhatAVerbTakesAwaySays => new WhatAVerbTakesAwaySays(self::AT_MOST, $seconds),
            unbounded: static fn(): WhatAVerbTakesAwaySays => new WhatAVerbTakesAwaySays(self::NO_BOUND, null),
        );
    }
}
