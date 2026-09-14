<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\TurnedDown;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;

/**
 * One request the household made, flattened for a template to read.
 *
 * {@see Wanted} answers its size through an `either()` and Blade has no way to
 * call one, so the fold happens once per row here — the argument
 * {@see WhatOneFindingSays} makes, and the reason that class exists.
 *
 * **The label and the figure are separate fields and both are always set.**
 * `D7-R3` wants the size shown and `D7-R4` wants an estimate labelled as one,
 * and a single pre-composed sentence would put the labelling in this class
 * where no translator can reach it (`L1`). So the template renders the key with
 * the figure passed in, and a row with nothing to show carries a key that says
 * so rather than an empty figure the template has to branch on.
 *
 * `Internal` because it is a detail of how one surface reads a value; `E2`'s
 * promise is that anything here can be renamed without reading another module.
 */
final readonly class WhatOneRequestSays
{
    /**
     * @param string $title         what was asked for, which is what a decision is made about
     * @param string $by            who asked, so a decline can reach them by name (`D7-R7`)
     * @param string $standing      the key for where the request stands
     * @param bool   $wantsADecision whether this is one `N2-R11` is about
     * @param string $sizeSaid   the key for how big it is, and how sure that is
     * @param int    $sizeFigure  the number that key is rendered with, whole and under a thousand
     * @param string $sizeUnit    the key for what the figure counts, or empty where there is none
     * @param string $refusedReason what they were told, where it was refused (`N3-R7`)
     * @param string $refusedAt     when, in the stack's own words, or empty where it said none
     *
     * The refusal is two fields rather than one composed sentence, for the size
     * pair's reason above: the reason is the stack's words and the moment is
     * the stack's words, and joining them here would put a sentence in this
     * class that no translator can reach (`L1`). Both are empty where the
     * request was not refused, and both are only ever filled with a standing of
     * `declined` — {@see Wanted::turnedDown()} is the only way such a row is
     * built.
     *
     * The figure and the unit both come off one {@see HowBig}, which is the
     * only place the bands are decided. Two ladders here — one picking the
     * divisor and one picking the word — were one decision spelled twice, with
     * thresholds that had to agree and nothing holding them to it.
     */
    private function __construct(
        public string $title,
        public string $by,
        public string $standing,
        public bool $wantsADecision,
        public string $sizeSaid,
        public int $sizeFigure,
        public string $sizeUnit,
        public string $refusedReason = '',
        public string $refusedAt = '',
    ) {}

    /**
     * Fold one request into the fields a row needs.
     *
     * The `either()` is answered here rather than in the screen, so a screen
     * showing requests is a loop over this and not a fold per row — and every
     * arm builds the whole row, which is {@see WhatOneFindingSays}' shape and
     * the reason a size can never reach a template without its label.
     */
    public static function in(Wanted $wanted): self
    {
        $standing = $wanted->standing();

        return $wanted->refusal(
            was: static fn(TurnedDown $why): self => self::built($wanted, $standing, $why),
            wasNot: static fn(): self => self::built($wanted, $standing),
        );
    }

    /**
     * The row itself, once the refusal has been answered.
     *
     * Split from {@see self::in()} so the size fold below happens once rather
     * than once per arm — the two questions are independent and writing them
     * nested would be four arms where there are two facts.
     */
    private static function built(Wanted $wanted, Waiting $standing, ?TurnedDown $why = null): self
    {
        $reason = $why instanceof TurnedDown ? $why->reason() : '';
        $at = $why instanceof TurnedDown ? $why->when(
            then: static fn(string $when): AsText => AsText::of($when),
            unstated: static fn(): AsText => AsText::of(''),
        )->said : '';

        return $wanted->size()->either(
            measured: static fn(int $bytes): self => new self(
                title: $wanted->forWhat(),
                by: $wanted->by(),
                standing: $standing->saidOnTheScreen(),
                wantsADecision: $standing->wantsADecision(),
                sizeSaid: 'household.size_measured',
                sizeFigure: HowBig::of($bytes)->figure,
                sizeUnit: HowBig::of($bytes)->said,
                refusedReason: $reason,
                refusedAt: $at,
            ),
            guessed: static fn(int $bytes): self => new self(
                title: $wanted->forWhat(),
                by: $wanted->by(),
                standing: $standing->saidOnTheScreen(),
                wantsADecision: $standing->wantsADecision(),
                sizeSaid: 'household.size_guessed',
                sizeFigure: HowBig::of($bytes)->figure,
                sizeUnit: HowBig::of($bytes)->said,
                refusedReason: $reason,
                refusedAt: $at,
            ),
            unknown: static fn(): self => new self(
                title: $wanted->forWhat(),
                by: $wanted->by(),
                standing: $standing->saidOnTheScreen(),
                wantsADecision: $standing->wantsADecision(),
                sizeSaid: 'household.size_unknown',
                // Nothing to render the sentence with, and *we do not know* is
                // a sentence that needs nothing. The figure is unread on this
                // arm — its key names no placeholder — which is why zero here
                // cannot become "0 bytes" beside a request for a whole season,
                // the outcome `D7-R3` calls worse than saying nothing.
                sizeFigure: 0,
                sizeUnit: '',
                refusedReason: $reason,
                refusedAt: $at,
            ),
        );
    }
}
