<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One request the household made, flattened for a template to read.
 *
 * {@see \Modules\Kernel\Api\Wanted} answers its size through an `either()` and
 * Blade has no way to call one, so
 * {@see \Modules\Operator\Internal\Presenters\HowARequestReads} folds it once
 * per row into this — the argument {@see WhatOneFindingSays} makes, and the
 * reason that class exists.
 *
 * **The label and the figure are separate fields and both are always set.**
 * The size has to be shown and an estimate labelled as one,
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
     * @param string $by            who asked, so a decline can reach them by name
     * @param string $standing      the key for where the request stands
     * @param bool   $wantsADecision whether this one is waiting on a decision
     * @param string $sizeSaid   the key for how big it is, and how sure that is
     * @param int    $sizeFigure  the number that key is rendered with, whole and under a thousand
     * @param string $sizeUnit    the key for what the figure counts, or empty where there is none
     * @param string $refusedReason what they were told, where it was refused
     * @param string $refusedAt     when, in the stack's own words, or empty where it said none
     *
     * The refusal is two fields rather than one composed sentence, for the size
     * pair's reason above: the reason is the stack's words and the moment is
     * the stack's words, and joining them here would put a sentence in this
     * class that no translator can reach (`L1`). Both are empty where the
     * request was not refused, and both are only ever filled with a standing of
     * `declined` — {@see \Modules\Kernel\Api\Wanted::turnedDown()} is the only
     * way such a row is built.
     *
     * The figure and the unit both come off one {@see \Modules\Kernel\Api\HowBig},
     * which is the only place the bands are decided. Two ladders here — one
     * picking the divisor and one picking the word — were one decision spelled
     * twice, with thresholds that had to agree and nothing holding them to it.
     */
    public function __construct(
        public int $number,
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
}
