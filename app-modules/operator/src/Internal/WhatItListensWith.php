<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Health\Api\KeepingTheLastReading;
use Modules\Kernel\Api\Capture;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Hearing;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Standings;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatWasHeard;

/**
 * The ports a screen holding the stream reaches for, handed to it together.
 *
 * {@see HearsHowTheStackIs} and {@see HearsHowEachStackIs} are where they are
 * used, and a screen is where they arrive, so the screen hands them over in one
 * value rather than the trait reaching for fields it cannot declare.
 */
final readonly class WhatItListensWith
{
    public function __construct(
        public Hearing $hearing,
        public Clock $clock,
        public Capture $capture,
        public Standings $standings,
        public KeepingTheLastReading $keeping,
    ) {}

    /**
     * Keep what a summary said, with when it was heard, and hand back what was heard.
     *
     * Twice over: the word for the list, and the whole summary for the next
     * time the stack's screen opens. What either store answers is not looked
     * at: a summary that could not be kept costs the list that row's word and
     * the next opening its first frame, and the screen that heard it has it
     * either way.
     */
    public function kept(WhatWasHeard $heard, Stack $stack, Instant $now): WhatWasHeard
    {
        $standings = $this->standings;
        $keeping = $this->keeping;

        return $heard->either(
            nothing: static fn(): WhatWasHeard => $heard,
            alive: static fn(): WhatWasHeard => $heard,
            said: static function (TheHealthSummary $summary) use ($standings, $keeping, $heard, $stack, $now): WhatWasHeard {
                $standings->remember($stack->id(), $summary->standing(), $now);
                $keeping->keep($stack->id(), $summary, $now);

                return $heard;
            },
            closed: static fn(): WhatWasHeard => $heard,
            met: static fn(): WhatWasHeard => $heard,
        );
    }
}
