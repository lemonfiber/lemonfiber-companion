<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A repair arrived without something `N2-R4` requires it to be offered with.
 *
 * Raised where the values become a `Repair`, for the reason
 * {@see RemedySaysNothing} gives: these are values that cannot be constructed
 * rather than refusals crossing a boundary, so there is nothing for a caller to
 * open (C1, C3).
 *
 * Named constructors rather than one taking which field was blank, because a
 * message assembled from a field name is a message nobody wrote: what is wrong
 * with a repair that will not say what it does is different from what is wrong
 * with one that stopped part-way, and a caller reading the log deserves the
 * difference.
 *
 * **A repair naming no check is not here.** The check is a {@see Check} before
 * it reaches {@see Repair::offered()}, so the blank is refused a layer down —
 * and the adapter refuses it a layer above that, where a payload short of a
 * field is something it can report as an obstacle rather than raise past its
 * own catch.
 */
final class RepairSaysNothing extends InvalidArgumentException
{
    public static function itDoes(): self
    {
        return new self('A repair arrived without saying what it does, and N2-R4 asks that to be stated before anybody is asked to agree to it.');
    }

    public static function whetherItLeftAnything(): self
    {
        return new self('A repair was recorded as stopped part-way without saying whether it left anything, and a stopped repair that left nothing is a different morning from one that did.');
    }

    public static function whatItLeft(): self
    {
        return new self('A repair said it stopped part-way and left something, and did not say what — which is a heading with nothing under it, and worse than the heading alone.');
    }
}
