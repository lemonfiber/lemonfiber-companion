<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One stack's last known verdict, flattened for a template to read.
 *
 * `Showing::either()` and `Reading::either()` both answer with an object, so
 * that a caller cannot take a verdict out without saying what happens when
 * there is none and without being handed the age beside it. That is the right
 * shape for a value and the wrong one for a Blade file, which has no `either()`
 * and cannot be given one — so
 * {@see \Modules\Operator\Internal\Presenters\HowAStacksAgeReads} folds each
 * stack into this once and the template reads fields.
 *
 * **The age comes out beside the word or not at all.** Both rules are
 * broken by omission rather than by disagreement: nobody decides to pass a
 * remembered verdict off as a current one, the timestamp is simply not at hand
 * where the screen is written. Here it cannot be missing, because the arm that
 * hands over the word hands over the moment in the same call — a row with a
 * verdict and no age would have to have been given one and dropped it.
 *
 * **Nothing here was read in this session.** Everything the opening screen
 * shows came out of a store, so every row carries an age and none of them may
 * stand as the confirmation of anything. The screen that asks a
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
    public function __construct(
        public string $said,
        public string $agoSaid,
        public int $agoCount,
        public bool $isKnown,
    ) {}
}
