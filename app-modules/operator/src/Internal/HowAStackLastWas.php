<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Overall;

/**
 * One stack's last known verdict, flattened for a template to read.
 *
 * `Showing::either()` and `Reading::either()` both answer with an object, so
 * that a caller cannot take a verdict out without saying what happens when
 * there is none and without being handed the age beside it. That is the right
 * shape for a value and the wrong one for a Blade file, which has no `either()`
 * and cannot be given one — so the screen folds each stack into this once and
 * the template reads fields.
 *
 * **The age comes out beside the word or not at all.** `N1-R9` and `N2-R13` are
 * broken by omission rather than by disagreement: nobody decides to pass a
 * remembered verdict off as a current one, the timestamp is simply not at hand
 * where the screen is written. Here it cannot be missing, because the arm that
 * hands over the word hands over the moment in the same call — a row with a
 * verdict and no age would have to have been given one and dropped it.
 *
 * **Nothing here was read in this session.** Everything the opening screen
 * shows came out of a store, so every row carries an age and none of them may
 * stand as the confirmation of anything (`N1-R24`). The screen that asks a
 * stack is the next one, and it asks.
 *
 * `Internal` because it is a detail of how this surface reads one value, and
 * `E2`'s promise is that anything here can be renamed without reading another
 * module.
 */
final readonly class HowAStackLastWas
{
    /**
     * @param string $said     the key for the verdict, or empty where none is held
     * @param string $agoSaid  the key for how long ago it was read, chosen by band
     * @param int    $agoCount how many of that band's unit, which the key counts on
     * @param bool   $isKnown  whether anything is held at all, which is what the template asks
     */
    private function __construct(
        public string $said,
        public string $agoSaid,
        public int $agoCount,
        public bool $isKnown,
    ) {}

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
    public static function read(Overall $overall, Instant $at, Instant $now): self
    {
        $unit = HowLongAgo::since($at, $now);

        return new self(
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
    public static function notYetKnown(): self
    {
        return new self('', '', 0, isKnown: false);
    }


}
