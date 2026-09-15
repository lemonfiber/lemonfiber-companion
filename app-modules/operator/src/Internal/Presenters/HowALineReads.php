<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Said;
use Modules\Operator\Internal\ViewModels\WhatOneLineSays;

/**
 * What one line a service wrote comes to, as the fields a row reads.
 *
 * **The moment is carried as the service stated it.** Not reformatted, not put
 * in the phone's timezone: a log line's timestamp is for matching it against
 * the lines around it, which are all in the service's own frame, and two
 * operators in different places must not disagree about when something happened
 * on the same machine.
 */
final readonly class HowALineReads
{
    /** Fold one line into the fields a row needs. */
    public function in(Said $said): WhatOneLineSays
    {
        $stream = $said->stream();

        return $said->when(
            then: static fn(string $when): WhatOneLineSays => new WhatOneLineSays(
                line: $said->line(),
                streamSaid: $stream->saidOnTheScreen(),
                worthNoticing: $stream->worthNoticing(),
                at: $when,
                hasAMoment: true,
            ),
            unstated: static fn(): WhatOneLineSays => new WhatOneLineSays(
                line: $said->line(),
                streamSaid: $stream->saidOnTheScreen(),
                worthNoticing: $stream->worthNoticing(),
                at: '',
                hasAMoment: false,
            ),
        );
    }
}
