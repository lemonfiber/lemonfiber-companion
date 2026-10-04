<?php

declare(strict_types=1);

namespace Modules\Updates\Api;

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
use Modules\Kernel\Api\Upkeep;
use Modules\Updates\Internal\TheUpkeepAsKept;
use Modules\Updates\Internal\UpkeepReadingsKept;

/**
 * What the phone keeps of where a stack stands on being up to date between launches, decided here.
 *
 * **The newest reading per stack, sealed before the store sees it.** Every
 * fresh reading replaces the one kept before it, and it goes to
 * {@see UpkeepReadingsKept} as a sealed payload under the stack's keyed hash,
 * so the store never holds a word it could read or a stack it could name.
 *
 * **Only the reading.** Never the offer a screen builds from it, an agreement
 * to take it, or what became of an update taken: each of those is given or
 * answered against a fresh reading, and none is written here.
 *
 * **On opening, the kept reading is what was last read, and not a live one.**
 * {@see lastKept()} hands a screen the reading with when it was read, which
 * the screen draws with its age, every action on it waiting, until a fresh
 * reading replaces it.
 *
 * **What does not read is let go of.** A kept reading in a shape this build
 * does not read, one that does not open, and one that opens to something that
 * is not a reading are all forgotten rather than shown.
 *
 * **For as long as the operator chose, then forgotten.** That is decided where
 * the choice is kept, with the phone's other settings, and asked of the store
 * directly: a reading too old to keep is let go of without being opened.
 */
final readonly class KeepingTheLastUpkeep implements ForgetsAStack
{
    public function __construct(
        private Sealed $seal,
        private UpkeepReadingsKept $kept,
    ) {}

    /** Keep this reading as the newest for this stack, sealed first. */
    public function keep(StackId $stack, Upkeep $upkeep, Instant $readAt): Noted
    {
        $written = TheUpkeepAsKept::written($upkeep);

        if (! $written instanceof Unsealed) {
            return Noted::notKept();
        }

        return $this->seal->seal($written)->either(
            sealed: fn(SealedPayload $payload): Noted => $this->kept->keep($this->seal->stack($stack), $payload, Shape::current(), $readAt),
            refused: static fn(): Noted => Noted::notKept(),
        );
    }

    /** What the screen for this stack holds before it has read anything: the kept reading as of when it was read, or nothing. */
    public function lastKept(StackId $stack): WhatWasKeptOfTheUpkeep
    {
        $sealed = $this->seal->stack($stack);

        return $this->kept->newest($sealed)->either(
            found: fn(SealedPayload $payload, Shape $shape, Instant $readAt): WhatWasKeptOfTheUpkeep => $this->seal->open($payload)->either(
                opened: fn(Unsealed $value): WhatWasKeptOfTheUpkeep => $this->readBack($sealed, TheUpkeepAsKept::read($shape, $value), $readAt),
                unreadable: fn(): WhatWasKeptOfTheUpkeep => $this->discarded($sealed),
            ),
            none: static fn(): WhatWasKeptOfTheUpkeep => WhatWasKeptOfTheUpkeep::nothing(),
            unreadable: fn(): WhatWasKeptOfTheUpkeep => $this->discarded($sealed),
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
    private function readBack(SealedStack $sealed, ?Upkeep $upkeep, Instant $readAt): WhatWasKeptOfTheUpkeep
    {
        return $upkeep instanceof Upkeep ? WhatWasKeptOfTheUpkeep::readAt($upkeep, $readAt) : $this->discarded($sealed);
    }

    private function discarded(SealedStack $sealed): WhatWasKeptOfTheUpkeep
    {
        $this->kept->forget($sealed);

        return WhatWasKeptOfTheUpkeep::nothing();
    }
}
