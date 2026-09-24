<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `stored` envelope did not hold what the contract says it holds.
 *
 * Every one of these is a bug somewhere other than here, so the message names
 * the list and the position: that is the only thing that shortens the search.
 * A developer reads it, so it is `sprintf` and never translated (`L1`).
 *
 * **Refused rather than salvaged.** A list of what a machine keeps that is one
 * row short is a thing somebody believes is not on their machine, and nothing
 * on the screen would say a row was dropped.
 */
final class StoredIsUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The stored envelope has no `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    public static function row(NamesAWireField $list, int $position): self
    {
        return new self(sprintf(
            'Entry %d of `%s` in the stored envelope is not an entry. It is refused rather than dropped: a list one row short says this machine does not keep something it does.',
            $position,
            $list->value,
        ));
    }

    public static function said(NamesAWireField $list, NamesAWireField $field, int $position): self
    {
        return new self(sprintf(
            'Entry %d of `%s` in the stored envelope has no readable `%s`. An entry that will not say what it is reads as complete to somebody deciding what to keep.',
            $position,
            $list->value,
            $field->value,
        ));
    }
}
