<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api;

use Modules\Health\Api\KeepingTheLastReading;
use Modules\Kernel\Api\Capture;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Hearing;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Notification;
use Modules\Kernel\Api\Notifier;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Standings;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Kernel\Api\WhereATapLeads;
use Modules\News\Api\Noticing;
use Modules\Stacks\Api\AStacksScreen;

/**
 * The ports a screen holding the stream reaches for, handed to it together.
 *
 * What hears the stream, the clock and whether anybody can see the screen,
 * where what was heard is kept, what answers how much is new by it, and where
 * the alerts it carried are told to the operator.
 *
 * The traits that hold a stream are where they are used, and a screen is where
 * they arrive, so the screen is handed them in one value and a trait holding a
 * stream reaches for that one value rather than for each port.
 * {@see HearingEachStack} listens with one.
 */
final readonly class WhatItListensWith
{
    public function __construct(
        public Hearing $hearing,
        public Clock $clock,
        public Capture $capture,
        public Standings $standings,
        public KeepingTheLastReading $keeping,
        public Noticing $noticing,
        public Notifier $notifier,
    ) {}

    /**
     * Tell the operator each alert the stack raised, keep what a summary said, and hand back what was heard.
     *
     * Each alert goes to the notifier as it arrived, filed under its name, so
     * a resolution replaces the onset it ends, and a tap on it opens the
     * stack's health.
     *
     * Twice over: the word for the list, and the whole summary for the next
     * time the stack's screen opens. What either store answers is not looked
     * at: a summary that could not be kept costs the list that row's word and
     * the next opening its first frame, and the screen that heard it has it
     * either way.
     */
    public function kept(WhatWasHeard $heard, Stack $stack, Instant $now): WhatWasHeard
    {
        foreach ($heard->theAlerts() as $alert) {
            $this->notifier->show(Notification::ofAnAlert($stack, $alert, WhereATapLeads::to(AStacksScreen::Health->forTheStack($stack->id()))));
        }

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
