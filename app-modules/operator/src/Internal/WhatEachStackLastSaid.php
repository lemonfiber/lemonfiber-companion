<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Standings;
use Modules\Operator\Internal\Presenters\HowTheOneLineReads;
use Modules\Operator\Internal\ViewModels\WhatTheOneLineSays;

/**
 * What a stack's one line last said, read from what the phone kept of it.
 *
 * The list of stacks and the list the top bar's name opens both say it, and
 * say the same word, so it is read in one place.
 */
final readonly class WhatEachStackLastSaid
{
    public function __construct(private Standings $standings, private Clock $clock) {}

    /**
     * The word, aged against now, or unknown where nothing was heard.
     *
     * Everything this reads came out of a store, so a live reading cannot
     * arrive. The arm is the type's, and a word with no moment has no age to
     * be said with, so it reads as never heard.
     */
    public function of(Stack $stack): WhatTheOneLineSays
    {
        $now = $this->clock->now();

        return $this->standings->lastKnownOf($stack->id())->either(
            waiting: static fn(): WhatTheOneLineSays => new HowTheOneLineReads()->neverHeard(),
            holding: static fn(Reading $reading): WhatTheOneLineSays => $reading->either(
                live: static fn(): WhatTheOneLineSays => new HowTheOneLineReads()->neverHeard(),
                retained: static fn(object $standing, Instant $at): WhatTheOneLineSays
                    => $standing instanceof HowItStands
                        ? new HowTheOneLineReads()->kept($standing, $at, $now)
                        : new HowTheOneLineReads()->neverHeard(),
            ),
        );
    }
}
