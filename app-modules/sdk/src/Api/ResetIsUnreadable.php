<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A `reset` envelope did not hold what the contract says one holds.
 *
 * Refused rather than read short: a preview missing a file or a connection is
 * a reset somebody would agree to without being told what it takes away.
 */
final class ResetIsUnreadable extends InvalidArgumentException
{
    /** A field the answer carries is absent, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The reset envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One entry of a list the answer carries is not what the contract says it is. */
    public static function entry(NamesAWireField $field, int $position): self
    {
        return new self(sprintf(
            'Entry %d of the reset\'s `%s` is not readable. What a reset takes away is shown whole or not at all.',
            $position,
            $field->value,
        ));
    }
}
