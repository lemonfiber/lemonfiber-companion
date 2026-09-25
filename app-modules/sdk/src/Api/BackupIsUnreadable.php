<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A `backup` envelope did not hold what the contract says one holds.
 *
 * Refused rather than read short: a report of a copy missing what it removed
 * is a report that leaves an operator believing they hold copies they do not.
 */
final class BackupIsUnreadable extends InvalidArgumentException
{
    /** A field the report carries is absent, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The backup envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One copy it removed is not a name. */
    public static function pruned(int $position): self
    {
        return new self(sprintf(
            'Entry %d of the copies a backup removed is not a name. What a copy removed is reported whole or not at all.',
            $position,
        ));
    }
}
