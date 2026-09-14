<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `repair` or `job` envelope did not hold what the contract says it holds.
 *
 * The same refusal {@see ReportIsUnreadable} is, for the payloads `N2-R4`'s
 * screen reads: every one of these is a bug somewhere other than here, and the
 * message names the field because that is the only thing that shortens the
 * search. A developer reads it, so it is `sprintf` and never translated (`L1`).
 *
 * **A listing is worth refusing rather than salvaging, and this one most of
 * all.** A report one finding short reads as a stack with one fewer problem; an
 * *offer* one repair short is a listing the operator is about to say yes to.
 * `N2-R6` has the yes quote the listing it was given, so a listing this app
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

    public static function repair(int $position): self
    {
        return new self(sprintf(
            'Repair %d in the offer is not a repair. The listing is refused rather than shown one short, because `N2-R6` has the operator agree to the listing they were given — and a listing missing a line is one they were not.',
            $position,
        ));
    }
}
