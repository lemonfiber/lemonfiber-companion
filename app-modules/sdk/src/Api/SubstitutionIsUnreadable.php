<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `substitution` envelope did not hold what the contract says it holds.
 *
 * {@see LinksAreUnreadable}'s refusal, for a choice of what fills a
 * capability. Refused rather than salvaged: a reading missing what it would
 * leave unfilled is a reading that says it leaves nothing, and an operator
 * agreeing to it agrees to a cost nobody showed them.
 */
final class SubstitutionIsUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The substitution envelope has no readable `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One row of a list was not readable, named by the list, the field and the row. */
    public static function entry(NamesAWireField $list, NamesAWireField $field, int $position): self
    {
        return new self(sprintf(
            'Row %d of `%s` in the substitution envelope has no readable `%s`. It is refused rather than dropped: a choice one row short states a cost smaller than the one it carries.',
            $position,
            $list->value,
            $field->value,
        ));
    }
}
