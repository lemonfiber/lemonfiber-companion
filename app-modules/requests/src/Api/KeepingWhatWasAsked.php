<?php

declare(strict_types=1);

namespace Modules\Requests\Api;

use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\SealStanding;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Unsealed;
use Modules\Requests\Internal\RequestsKept;
use Modules\Requests\Internal\TheRequestsAsKept;

/**
 * What the phone keeps of what a stack's household asked for between launches, decided here.
 *
 * **The newest reading per stack, sealed before the store sees it**, read at
 * the moment it is kept. Every fresh reading replaces the one kept before it,
 * and it goes to {@see RequestsKept} as a sealed payload under the stack's
 * keyed hash, so the store never holds a word it could read or a stack it
 * could name.
 *
 * **Only the reading.** Never an approval, a decline, the reason an operator
 * typed for one, or what the stack answered when it was told: each of those is
 * given against a fresh reading, and none is handed here.
 *
 * **Handed back with when it was read, and now**, so a screen draws its age
 * with every decision on it waiting until a fresh reading replaces it.
 *
 * **What does not read is let go of.** A kept reading in a shape this build
 * does not read, one that does not open, and one that opens to something that
 * is not a reading of what was asked are all forgotten rather than shown.
 *
 * **For as long as the operator chose, then forgotten.** That is decided where
 * the choice is kept, with the phone's other settings, and asked of the store
 * directly: a reading too old to keep is let go of without being opened.
 */
final readonly class KeepingWhatWasAsked implements ForgetsAStack
{
    public function __construct(
        private Sealed $seal,
        private RequestsKept $kept,
        private Clock $clock,
    ) {}

    /** Keep this reading as the newest for this stack, read now, sealed first. */
    public function keep(StackId $stack, Requested $requested): Noted
    {
        $written = TheRequestsAsKept::written($requested);

        if (! $written instanceof Unsealed) {
            return Noted::notKept();
        }

        return $this->seal->seal($written)->either(
            sealed: fn(SealedPayload $payload): Noted => $this->kept->keep($this->seal->stack($stack), $payload, Shape::current(), $this->clock->now()),
            refused: static fn(): Noted => Noted::notKept(),
        );
    }

    /** What a screen for this stack holds before it has read anything: the kept reading as of when it was read, or nothing. */
    public function lastKept(StackId $stack): WhatWasKeptOfWhatWasAsked
    {
        $sealed = $this->seal->stack($stack);

        return $this->kept->newest($sealed)->either(
            found: fn(SealedPayload $payload, Shape $shape, Instant $readAt): WhatWasKeptOfWhatWasAsked => $this->seal->open($payload)->either(
                opened: fn(Unsealed $value): WhatWasKeptOfWhatWasAsked => $this->readBack($sealed, TheRequestsAsKept::read($shape, $value), $readAt),
                unreadable: fn(): WhatWasKeptOfWhatWasAsked => $this->discarded($sealed),
            ),
            none: static fn(): WhatWasKeptOfWhatWasAsked => WhatWasKeptOfWhatWasAsked::nothing(),
            unreadable: fn(): WhatWasKeptOfWhatWasAsked => $this->discarded($sealed),
        );
    }

    /** Let go of the reading kept for this stack. */
    public function forgetTheStack(StackId $stack): Forgotten
    {
        return $this->kept->forget($this->seal->stack($stack));
    }

    /**
     * Whether a reading is kept for this stack, or might be.
     *
     * Where the seal's keys cannot be read, a stack's hash matches no row, so
     * the store cannot say; that is answered as might be, and a removal waits
     * for the keys rather than leaving a reading behind it.
     */
    public function keepsAnythingOf(StackId $stack): bool
    {
        if ($this->seal->standing() === SealStanding::Unavailable) {
            return true;
        }

        return $this->kept->newest($this->seal->stack($stack))->holdsARow();
    }

    /** The reading that opened, or nothing and the row let go of where it did not read as one. */
    private function readBack(SealedStack $sealed, ?Requested $requested, Instant $readAt): WhatWasKeptOfWhatWasAsked
    {
        return $requested instanceof Requested ? WhatWasKeptOfWhatWasAsked::readAt($requested, $readAt, $this->clock->now()) : $this->discarded($sealed);
    }

    private function discarded(SealedStack $sealed): WhatWasKeptOfWhatWasAsked
    {
        $this->kept->forget($sealed);

        return WhatWasKeptOfWhatWasAsked::nothing();
    }
}
