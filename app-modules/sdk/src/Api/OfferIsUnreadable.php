<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `repair` or `job` envelope did not hold what the contract says it holds.
 *
 * The same refusal {@see ReportIsUnreadable} is, for the payloads the offer
 * screen reads: every one of these is a bug somewhere other than here, and the
 * message names the field because that is the only thing that shortens the
 * search. A developer reads it, so it is `sprintf` and never translated (`L1`).
 *
 * **A listing is worth refusing rather than salvaging, and this one most of
 * all.** A report one finding short reads as a stack with one fewer problem; an
 * *offer* one repair short is a listing the operator is about to say yes to.
 * A yes quotes the listing it was given, so a listing this app
 * could not read whole is one it must not present at all — the operator would
 * be agreeing to something nobody showed them.
 */
final class OfferIsUnreadable extends InvalidArgumentException
{
    public static function missing(WireField $field): self
    {
        return new self(sprintf(
            'The answer has no `%s`, or it is not what the contract says it is. This did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /**
     * One record of what became of a repair.
     *
     * Its own refusal rather than {@see self::repair()}, because the two are
     * read in different places and mean different things: one is a listing the
     * operator has not agreed to yet, and this is a record of what a machine
     * already did. A reader who cannot tell which they are looking at cannot
     * tell whether anything happened.
     */
    public static function outcome(int $position): self
    {
        return new self(sprintf(
            'Outcome %d is not a record of a repair. It is refused rather than dropped, because a run reported one outcome short reads as a repair nobody agreed to — and the operator is left not knowing what their machine did.',
            $position,
        ));
    }

    public static function word(string $said): self
    {
        return new self(sprintf(
            'A repair reports becoming `%s`, and this app does not read that word. Guessing is how a repair that overwrote nothing gets shown as one that worked.',
            $said,
        ));
    }

    public static function repair(int $position): self
    {
        return new self(sprintf(
            'Repair %d in the offer is not a repair. The listing is refused rather than shown one short, because `N2-R6` has the operator agree to the listing they were given — and a listing missing a line is one they were not.',
            $position,
        ));
    }
}
