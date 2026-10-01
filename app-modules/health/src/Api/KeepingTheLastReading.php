<?php

declare(strict_types=1);

namespace Modules\Health\Api;

use function max;

use Modules\Health\Internal\HealthReadingsKept;
use Modules\Health\Internal\TheSummaryAsKept;
use Modules\Health\Internal\WhatTheKeptSummaryHeld;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\HowLongReadingsAreKept;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KeepsReadingsFor;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\SecondsIn;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\Unsealed;

/**
 * What the phone keeps of a stack's health between launches, decided here.
 *
 * **The newest summary per stack, sealed before the store sees it.** Every
 * fresh summary replaces the one kept before it, and it goes to
 * {@see HealthReadingsKept} as a sealed payload under the stack's keyed hash,
 * so the store never holds a word it could read or a stack it could name.
 *
 * **On opening, the kept summary is what was last heard, and not a live one.**
 * {@see lastKept()} hands a screen a {@see WhatWasHeardSoFar} holding it as of
 * when it was read, which the screen draws with its age until a fresh summary
 * replaces it.
 *
 * **What does not read is let go of.** A kept summary in a shape this build
 * does not read, one that does not open, and one that opens to something that
 * is not a summary are all forgotten rather than shown: a reading can always
 * be read again from the stack.
 *
 * **For as long as the operator chose, then forgotten.** How long is
 * {@see HowLongReadingsAreKept::standard()} until the operator chooses on App
 * settings, and the choice is kept with the phone's other settings. Every
 * reading read longer ago than that is let go of when the app opens, and again
 * the moment the operator chooses a shorter time.
 *
 * Where nothing can be sealed, nothing is kept; and where the seal's keys
 * cannot be read, a stack's hash matches no row, so nothing is found and
 * nothing is let go of — what was sealed stays for the day the keys read again.
 */
final readonly class KeepingTheLastReading
{
    public function __construct(
        private Sealed $seal,
        private HealthReadingsKept $kept,
        private KeepsReadingsFor $period,
        private Clock $clock,
    ) {}

    /** Keep this summary as the newest for this stack, sealed first. */
    public function keep(StackId $stack, TheHealthSummary $summary, Instant $readAt): Noted
    {
        $written = TheSummaryAsKept::written($summary);

        if (! $written instanceof Unsealed) {
            return Noted::notKept();
        }

        return $this->seal->seal($written)->either(
            sealed: fn(SealedPayload $payload): Noted => $this->kept->keep($this->seal->stack($stack), $payload, Shape::current(), $readAt),
            refused: static fn(): Noted => Noted::notKept(),
        );
    }

    /** What a screen for this stack holds on opening: the kept summary as of when it was read, or nothing. */
    public function lastKept(StackId $stack): WhatWasHeardSoFar
    {
        $sealed = $this->seal->stack($stack);

        return $this->kept->newest($sealed)->either(
            found: fn(SealedPayload $payload, Shape $shape, Instant $readAt): WhatWasHeardSoFar => $this->opened($sealed, $payload, $shape, $readAt),
            none: static fn(): WhatWasHeardSoFar => WhatWasHeardSoFar::nothingYet(),
            unreadable: fn(): WhatWasHeardSoFar => $this->discarded($sealed),
        );
    }

    /** Let go of every reading kept longer than readings are kept for, as of now. */
    public function forgetTheOld(Instant $now): Forgotten
    {
        return $this->forgetOlderThan($this->keptFor(), $now);
    }

    /** How long readings are kept, as the operator chose, or the standard. */
    public function keptFor(): HowLongReadingsAreKept
    {
        return $this->period->keptFor();
    }

    /**
     * Keep the operator's choice of how long, let go of every reading older
     * than it now, and answer what is in force.
     */
    public function keepFor(HowLongReadingsAreKept $kept): HowLongReadingsAreKept
    {
        $inForce = $this->period->keepFor($kept);
        $this->forgetOlderThan($inForce, $this->clock->now());

        return $inForce;
    }

    /**
     * Let go of every reading older than that length allows, as of now.
     *
     * Never asking about a moment before the epoch: a clock reading less than
     * the length kept keeps everything.
     */
    private function forgetOlderThan(HowLongReadingsAreKept $kept, Instant $now): Forgotten
    {
        return $kept->either(
            days: fn(int $days): Forgotten => $this->kept->forgetOlderThan(
                Instant::atEpochSeconds(max(0, $now->epochSeconds() - $days * SecondsIn::ADay->value)),
            ),
            untilRemoved: static fn(): Forgotten => Forgotten::nothing(),
        );
    }

    private function opened(SealedStack $sealed, SealedPayload $payload, Shape $shape, Instant $readAt): WhatWasHeardSoFar
    {
        $summary = $this->seal->open($payload)->either(
            opened: static fn(Unsealed $value): WhatTheKeptSummaryHeld => new WhatTheKeptSummaryHeld(TheSummaryAsKept::read($shape, $value)),
            unreadable: static fn(): WhatTheKeptSummaryHeld => new WhatTheKeptSummaryHeld(null),
        )->summary;

        return $summary instanceof TheHealthSummary
            ? WhatWasHeardSoFar::keptFrom($summary, $readAt)
            : $this->discarded($sealed);
    }

    private function discarded(SealedStack $sealed): WhatWasHeardSoFar
    {
        $this->kept->forget($sealed);

        return WhatWasHeardSoFar::nothingYet();
    }
}
