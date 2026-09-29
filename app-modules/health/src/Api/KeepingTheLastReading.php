<?php

declare(strict_types=1);

namespace Modules\Health\Api;

use function max;

use Modules\Health\Internal\HealthReadingsKept;
use Modules\Health\Internal\TheSummaryAsKept;
use Modules\Health\Internal\WhatTheKeptSummaryHeld;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
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
 * **Thirty days, then forgotten.** At launch every reading read longer ago than
 * that is let go of. The operator's own choice of how long replaces the thirty
 * when there is a setting to make it in.
 *
 * Where nothing can be sealed, nothing is kept; and where the seal's keys
 * cannot be read, a stack's hash matches no row, so nothing is found and
 * nothing is let go of — what was sealed stays for the day the keys read again.
 */
final readonly class KeepingTheLastReading
{
    /** How many days a kept reading is kept for until the operator says otherwise. */
    private const int DAYS_KEPT = 30;

    /** A day, in the seconds an {@see Instant} counts. */
    private const int SECONDS_IN_A_DAY = 86_400;

    public function __construct(private Sealed $seal, private HealthReadingsKept $kept) {}

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

    /** Let go of every reading kept longer than a reading is kept for, as of now. */
    public function forgetTheOld(Instant $now): Forgotten
    {
        return $this->kept->forgetOlderThan(
            Instant::atEpochSeconds(max(0, $now->epochSeconds() - self::DAYS_KEPT * self::SECONDS_IN_A_DAY)),
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
