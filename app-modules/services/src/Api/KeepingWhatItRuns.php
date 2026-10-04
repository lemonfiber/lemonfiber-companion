<?php

declare(strict_types=1);

namespace Modules\Services\Api;

use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\SealStanding;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Unsealed;
use Modules\Services\Internal\ListingsKept;
use Modules\Services\Internal\TheListingAsKept;

/**
 * What the phone keeps of what a stack runs between launches, decided here.
 *
 * **The newest listing per stack, sealed before the store sees it**, read at
 * the moment it is kept. Every fresh listing replaces the one kept before it,
 * and it goes to {@see ListingsKept} as a sealed payload under the stack's
 * keyed hash, so the store never holds a word it could read or a stack it
 * could name.
 *
 * **Only the listing.** Never an action, its confirmation or what a start
 * waits on: each of those is given or answered against a fresh listing, and
 * none is handed here.
 *
 * **Handed back with when it was read, and now**, so a screen draws its age
 * with every action on it waiting until a fresh listing replaces it.
 *
 * **What does not read is let go of.** A kept listing in a shape this build
 * does not read, one that does not open, and one that opens to something that
 * is not a listing are all forgotten rather than shown.
 *
 * **For as long as the operator chose, then forgotten.** That is decided where
 * the choice is kept, with the phone's other settings, and asked of the store
 * directly: a listing too old to keep is let go of without being opened.
 */
final readonly class KeepingWhatItRuns implements ForgetsAStack
{
    public function __construct(
        private Sealed $seal,
        private ListingsKept $kept,
        private Clock $clock,
    ) {}

    /** Keep this listing as the newest for this stack, read now, sealed first. */
    public function keep(StackId $stack, Daemons $daemons): Noted
    {
        $written = TheListingAsKept::written($daemons);

        if (! $written instanceof Unsealed) {
            return Noted::notKept();
        }

        return $this->seal->seal($written)->either(
            sealed: fn(SealedPayload $payload): Noted => $this->kept->keep($this->seal->stack($stack), $payload, Shape::current(), $this->clock->now()),
            refused: static fn(): Noted => Noted::notKept(),
        );
    }

    /** What a screen for this stack holds before it has read anything: the kept listing as of when it was read, or nothing. */
    public function lastKept(StackId $stack): WhatWasKeptOfWhatItRuns
    {
        $sealed = $this->seal->stack($stack);

        return $this->kept->newest($sealed)->either(
            found: fn(SealedPayload $payload, Shape $shape, Instant $readAt): WhatWasKeptOfWhatItRuns => $this->seal->open($payload)->either(
                opened: fn(Unsealed $value): WhatWasKeptOfWhatItRuns => $this->readBack($sealed, TheListingAsKept::read($shape, $value), $readAt),
                unreadable: fn(): WhatWasKeptOfWhatItRuns => $this->discarded($sealed),
            ),
            none: static fn(): WhatWasKeptOfWhatItRuns => WhatWasKeptOfWhatItRuns::nothing(),
            unreadable: fn(): WhatWasKeptOfWhatItRuns => $this->discarded($sealed),
        );
    }

    /** Let go of the listing kept for this stack. */
    public function forgetTheStack(StackId $stack): Forgotten
    {
        return $this->kept->forget($this->seal->stack($stack));
    }

    /**
     * Whether a listing is kept for this stack, or might be.
     *
     * Where the seal's keys cannot be read, a stack's hash matches no row, so
     * the store cannot say; that is answered as might be, and a removal waits
     * for the keys rather than leaving a listing behind it.
     */
    public function keepsAnythingOf(StackId $stack): bool
    {
        if ($this->seal->standing() === SealStanding::Unavailable) {
            return true;
        }

        return $this->kept->newest($this->seal->stack($stack))->holdsARow();
    }

    /** The listing that opened, or nothing and the row let go of where it did not read as one. */
    private function readBack(SealedStack $sealed, ?Daemons $daemons, Instant $readAt): WhatWasKeptOfWhatItRuns
    {
        return $daemons instanceof Daemons ? WhatWasKeptOfWhatItRuns::readAt($daemons, $readAt, $this->clock->now()) : $this->discarded($sealed);
    }

    private function discarded(SealedStack $sealed): WhatWasKeptOfWhatItRuns
    {
        $this->kept->forget($sealed);

        return WhatWasKeptOfWhatItRuns::nothing();
    }
}
