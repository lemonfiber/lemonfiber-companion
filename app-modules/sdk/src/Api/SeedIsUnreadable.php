<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `seed` envelope did not hold what the contract says it holds.
 *
 * A developer reads it, so it is `sprintf` and never translated. Refused rather
 * than salvaged: a state read as the nearest one could call a failure wired,
 * and a run shown one connection short hides one that broke.
 */
final class SeedIsUnreadable extends InvalidArgumentException
{
    /** A field of the envelope itself is absent, blank, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The seed envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One connection, or one entry of what could not be wired, does not say what it owes. */
    public static function entry(NamesAWireField $list, int $position, NamesAWireField $field): self
    {
        return new self(sprintf(
            'Entry %d of `%s` in the seed envelope has no readable `%s`. It is refused rather than dropped: a run shown one connection short hides one that broke.',
            $position,
            $list->value,
            $field->value,
        ));
    }
}
