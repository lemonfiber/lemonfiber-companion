<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/** A `preview` envelope did not hold what the contract says one holds. */
final class RehearsalIsUnreadable extends InvalidArgumentException
{
    /** A list the rehearsal carries is absent, or not a list. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The preview envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One entry of a list is not what the contract says it is. */
    public static function entry(NamesAWireField $list, int $position): self
    {
        return new self(sprintf(
            'Entry %d of the preview\'s `%s` is not readable. A rehearsal is drawn whole or not at all.',
            $position,
            $list->value,
        ));
    }
}
