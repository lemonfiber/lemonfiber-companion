<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_find;
use function sprintf;

/**
 * The unit a size is said in, and the only place the bands are decided.
 *
 * A size is shown before a request is approved, and *how many bytes*
 * has to become words somewhere. This is that somewhere — the sibling of
 * {@see HowLongAgo} and written the same way, for the same reason: a second
 * screen phrasing a size its own way is how two screens come to disagree about
 * what *large* means.
 *
 * **Decimal, not binary.** A thousand rather than 1024, because the operator is
 * comparing this against a number printed on a box rather than against what a
 * filesystem reports.
 *
 * **Declared smallest first**, which {@see HowBig} reads by walking the cases
 * backwards: the largest unit a size fills at least one of is the one it is
 * said in. The order is the meaning, so moving a case is a failing test rather
 * than a screen that quietly starts counting in terabytes.
 *
 * **One band table, not two.** {@see HowBig} divides by the unit's own size, so
 * the unit and the number beside it cannot disagree — a second `match` choosing
 * the divisor would be one decision spelled twice, which is the shape that
 * drifts. It drifted here first: this replaces two parallel ladders whose
 * thresholds had to agree and whose agreement nothing enforced.
 *
 * **Nothing here takes a number.** `D2` puts a primitive across a module
 * boundary in one place only, a static named constructor answering its own
 * type, and that is {@see HowBig::of()}. What is left here answers about
 * itself: how big one of these is, which one is above it, and what it is
 * called.
 *
 * **Whole numbers under a thousand, which is `L5` deciding the shape.** Dutch
 * writes 1.234,5 where English writes 1,234.5, so a separator written into a
 * source file is wrong in one locale by construction. Three units and a ceiling
 * of a thousand mean every figure that leaves here is an integer between nought
 * and 999, which has no separator to get wrong in any language.
 *
 * The precision lost is precision the number did not have. The label exists
 * because most of these are estimates, and *is it 4 or 400* is the whole of
 * what an operator is deciding.
 */
enum SizeUnit: string
{
    case Megabytes = 'megabytes';

    case Gigabytes = 'gigabytes';

    case Terabytes = 'terabytes';

    /** A thousand thousand, which is the smallest unit shown. */
    private const int A_MEGABYTE = 1_000_000;

    /** A thousand of those. */
    private const int A_GIGABYTE = 1_000_000_000;

    /** A thousand of those, which is where a season of anything ends up. */
    private const int A_TERABYTE = 1_000_000_000_000;

    /** How many bytes one of this unit is, which is what the bands are. */
    public function bytes(): int
    {
        return match ($this) {
            self::Megabytes => self::A_MEGABYTE,
            self::Gigabytes => self::A_GIGABYTE,
            self::Terabytes => self::A_TERABYTE,
        };
    }

    /**
     * The unit above this one, or this one where there is none.
     *
     * The largest band answers itself, and it cannot be reached by the escape
     * that calls this: a figure only leaves its band by rounding up into the
     * next, and above the largest there is nothing to round into.
     */
    public function next(): self
    {
        // Found by size rather than by position, so the order stays one table.
        // Walking `cases()` with an index would be the declaration order
        // written a second time, and a case moved without its size moved would
        // put the two out of step silently.
        return array_find(
            self::cases(),
            fn(self $unit): bool => $unit->bytes() > $this->bytes(),
        ) ?? $this;
    }

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, which is the shape every word in this app reaches
     * the catalogue by. A key rather than `'GB'`, because `L1` has every word
     * an operator reads come from the translator — and these are words in some
     * languages even where they are letters in ours.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('household.%s', $this->value);
    }
}
