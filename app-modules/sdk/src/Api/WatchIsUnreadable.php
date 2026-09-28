<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/** A `watch` envelope did not hold what the contract says one holds. */
final class WatchIsUnreadable extends InvalidArgumentException
{
    /** A part the report carries is absent, or not what the contract says. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The watch envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One of the forms it names is not a form's name. */
    public static function form(int $position): self
    {
        return new self(sprintf(
            'Entry %d of the watch\'s `forms` is not a form\'s name. What a guard stopped is drawn whole or not at all.',
            $position,
        ));
    }
}
