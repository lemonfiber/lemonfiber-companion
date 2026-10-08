<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `plugins` envelope did not hold what the contract says it holds.
 *
 * {@see HistoryIsUnreadable}'s refusal, for what a stack says of its plugins.
 * Refused rather than salvaged: a pair dropped for being unreadable is a value
 * the operator agrees to send without being told, and a listing one plugin
 * short is a plugin running that nobody can see.
 */
final class PluginsAreUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The plugins envelope has no readable `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One row of a list was not readable, named by the list, the field and the row. */
    public static function entry(NamesAWireField $list, NamesAWireField $field, int $position): self
    {
        return new self(sprintf(
            'Row %d of `%s` in the plugins envelope has no readable `%s`. It is refused rather than dropped: an account one row short is something the operator agrees to without being told.',
            $position,
            $list->value,
            $field->value,
        ));
    }

    /** A word arrived that this app has no reading for, named by where it was. */
    public static function said(NamesAWireField $field, string $said): self
    {
        return new self(sprintf(
            'The plugins envelope says `%s` for `%s`, which this app has no reading for.',
            $said,
            $field->value,
        ));
    }
}
