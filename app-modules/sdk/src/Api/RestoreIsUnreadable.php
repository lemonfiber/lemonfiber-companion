<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A `restore` envelope did not hold what the contract says one holds.
 *
 * Refused rather than read short: a listing missing where the data would go
 * is a restore somebody would agree to without being told.
 */
final class RestoreIsUnreadable extends InvalidArgumentException
{
    /** A field the answer carries is absent, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The restore envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One thing the copy holds is not what the contract says it is. */
    public static function member(int $position): self
    {
        return new self(sprintf(
            'Entry %d of what a copy holds is not readable. What putting it back would overwrite is listed whole or not at all.',
            $position,
        ));
    }
}
