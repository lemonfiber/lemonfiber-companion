<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A `handoff` envelope arrived without something handing a device over needs.
 *
 * Refused rather than shown: a hand-off drawn from half an answer is a code, or
 * a list of steps, somebody follows on a device and gets nowhere with.
 */
final class HandoffIsUnreadable extends InvalidArgumentException
{
    /** A field the answer carries is absent, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The handoff envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One entry of a list, or one of its fields, is not what the contract says it is. */
    public static function row(NamesAWireField $list, int $position): self
    {
        return new self(sprintf(
            'Entry %d of `%s` in the handoff envelope is not readable. It is refused rather than dropped: a list one row short reads as complete.',
            $position,
            $list->value,
        ));
    }

    /** A word a closed set names, which this app has no case for. */
    public static function unnamed(NamesAWireField $field, string $said): self
    {
        return new self(sprintf(
            'The handoff envelope says `%s` for `%s`, which this app has no case for. Drawing it as the nearest one would be a guess.',
            $said,
            $field->value,
        ));
    }
}
