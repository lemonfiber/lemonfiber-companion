<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/** A `lifecycle` envelope did not hold what the contract says one holds. */
final class LifecycleIsUnreadable extends InvalidArgumentException
{
    /** A part the report carries is absent, or not what the contract says. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The lifecycle envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One entry of a list is not what the contract says it is. */
    public static function entry(NamesAWireField $list, int $position): self
    {
        return new self(sprintf(
            'Entry %d of the lifecycle\'s `%s` is not readable. What a verb came to is drawn whole or not at all.',
            $position,
            $list->value,
        ));
    }
}
