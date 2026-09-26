<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A `bundle` envelope did not hold what the contract says one holds.
 *
 * Refused rather than read short: a bundle shown without one of its files is
 * one an operator hands over believing they have read all of it, and one shown
 * without what could not be collected reads as a machine with nothing wrong.
 */
final class BundleIsUnreadable extends InvalidArgumentException
{
    /** A field the bundle carries is absent, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The bundle envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One entry of a list the bundle carries is not what the contract says it is. */
    public static function entry(NamesAWireField $field, int $position): self
    {
        return new self(sprintf(
            'Entry %d of the bundle\'s `%s` is not readable. What a bundle holds is shown whole or not at all.',
            $position,
            $field->value,
        ));
    }
}
