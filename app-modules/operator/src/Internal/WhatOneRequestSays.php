<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Wanted;

use function round;

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
     * What a thousand is for a size, which is what a disk is sold in.
     *
     * Decimal rather than binary because the operator is comparing this against
     * a number printed on a box, not against what a filesystem reports.
     */
    private const int A_THOUSAND = 1000;

    /** Bytes in a megabyte, which is the smallest unit shown. */
    private const int A_MEGABYTE = self::A_THOUSAND ** 2;

    /** Bytes in a gigabyte. */
    private const int A_GIGABYTE = self::A_THOUSAND ** 3;

    /** Bytes in a terabyte, which is where a season of anything ends up. */
    private const int A_TERABYTE = self::A_THOUSAND ** 4;

    /**
     * @param string $title         what was asked for, which is what a decision is made about
     * @param string $by            who asked, so a decline can reach them by name (`D7-R7`)
     * @param string $standing      the key for where the request stands
     * @param bool   $wantsADecision whether this is one `N2-R11` is about
     * @param string $sizeSaid   the key for how big it is, and how sure that is
     * @param int    $sizeFigure  the number that key is rendered with, whole and under a thousand
     * @param string $sizeUnit    the key for what the figure counts, or empty where there is none
     */
    private function __construct(
        public string $title,
        public string $by,
        public string $standing,
        public bool $wantsADecision,
        public string $sizeSaid,
        public int $sizeFigure,
        public string $sizeUnit,
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

        return $wanted->size()->either(
            measured: static fn(int $bytes): self => new self(
                title: $wanted->forWhat(),
                by: $wanted->by(),
                standing: $standing->saidOnTheScreen(),
                wantsADecision: $standing->wantsADecision(),
                sizeSaid: 'household.size_measured',
                sizeFigure: self::figureFor($bytes),
                sizeUnit: self::unitFor($bytes),
            ),
            guessed: static fn(int $bytes): self => new self(
                title: $wanted->forWhat(),
                by: $wanted->by(),
                standing: $standing->saidOnTheScreen(),
                wantsADecision: $standing->wantsADecision(),
                sizeSaid: 'household.size_guessed',
                sizeFigure: self::figureFor($bytes),
                sizeUnit: self::unitFor($bytes),
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
            ),
        );
    }

    /**
     * The figure, in whichever unit keeps it under a thousand.
     *
     * Whole rather than to a decimal place, and that is `L5` deciding the shape
     * rather than a preference: Dutch writes 1.234,5 where English writes
     * 1,234.5, so a separator written into this file is wrong in one locale by
     * construction. Three units and a ceiling of a thousand means every figure
     * that leaves here is an integer between zero and 999, which has no
     * separator to get wrong in any language.
     *
     * The precision lost is precision this number did not have. `D7-R4` exists
     * because most of these are estimates, and a tenth of a gigabyte on a guess
     * is a claim nobody can stand behind — while *is it 4 or 400* is the whole
     * of what an operator is deciding.
     */
    private static function figureFor(int $bytes): int
    {
        return (int) match (true) {
            $bytes >= self::A_TERABYTE => round($bytes / self::A_TERABYTE),
            $bytes >= self::A_GIGABYTE => round($bytes / self::A_GIGABYTE),
            default => round($bytes / self::A_MEGABYTE),
        };
    }

    /**
     * What that figure counts, as a key.
     *
     * A key rather than `'GB'`, because `L1` has every word an operator reads
     * come from the translator — and these are words in some languages even
     * where they are letters in ours.
     */
    private static function unitFor(int $bytes): string
    {
        return match (true) {
            $bytes >= self::A_TERABYTE => 'household.terabytes',
            $bytes >= self::A_GIGABYTE => 'household.gigabytes',
            default => 'household.megabytes',
        };
    }
}
