<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Overall;
use Modules\Operator\Internal\ViewModels\HowAStackLastWas;

/**
 * One stack's last known verdict, turned into the word and the age a row reads.
 *
 * `F2`: data in, view model out. The moment the age is measured against is an
 * argument rather than a clock this holds, which is `B1` — time is an input,
 * and one frame's worth of rows can therefore be aged against the single *now*
 * the screen read.
 */
final readonly class HowAStacksAgeReads
{
    /**
     * A verdict is held, and this is what it said and how long ago that was.
     *
     * The moment is turned into an age here rather than shown as a timestamp,
     * because what the operator is deciding is whether this is fresh — and
     * `2026-09-13T22:14:03` makes them do the subtraction. The catalogue line
     * was written for a phrase: `Last checked :ago`.
     *
     * **Three bands, and the clock is handed in.** Time is an input (`B1`), so
     * *this verdict is two hours old* is a sentence a test states rather than a
     * moment it has to arrange. The bands are coarse on purpose: past a day,
     * knowing whether it was thirty-one or thirty-two hours changes nothing an
     * operator does, and precision that changes nothing is noise on a phone.
     */
    public function read(Overall $overall, Instant $at, Instant $now): HowAStackLastWas
    {
        $unit = HowLongAgo::since($at, $now);

        return new HowAStackLastWas(
            $overall->saidOnTheScreen(),
            $unit->saidOnTheScreen(),
            $unit->howManySince($at, $now),
            isKnown: true,
        );
    }

    /**
     * Nothing is held for this stack yet.
     *
     * A stack paired and never asked, which is an ordinary state rather than a
     * defensive one: pairing and asking are separate screens and an operator
     * can leave between them. The row shows the machine and offers the way in,
     * which is what it did before any of this existed.
     */
    public function notYetKnown(): HowAStackLastWas
    {
        return new HowAStackLastWas('', '', 0, isKnown: false);
    }
}
