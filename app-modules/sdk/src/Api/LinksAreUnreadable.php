<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `wiring` envelope did not hold what the contract says it holds.
 *
 * {@see CatalogueIsUnreadable}'s refusal, for what the stack wires to what.
 * Refused rather than salvaged: a link dropped for being unreadable is a link
 * the screen says the stack does not have, and a settlement read as another is
 * an answer the stack never gave.
 */
final class LinksAreUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The wiring envelope has no `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One row of a list was not readable, named by the list, the field and the row. */
    public static function entry(NamesAWireField $list, NamesAWireField $field, int $position): self
    {
        return new self(sprintf(
            'Row %d of `%s` in the wiring envelope has no readable `%s`. It is refused rather than dropped: a wiring one row short says the stack does not have a link it has.',
            $position,
            $list->value,
            $field->value,
        ));
    }

    /** Where one claimant came from could not be read, which the screen would otherwise say as the stack's own. */
    public static function origin(int $position, OriginIsUnreadable $why): self
    {
        return new self(sprintf(
            'Row %d of `wired` in the wiring envelope names a claimant whose origin cannot be read: %s',
            $position,
            $why->getMessage(),
        ), previous: $why);
    }

    /** A field carried a word this app has no case for. */
    public static function unnamed(NamesAWireField $field, string $said, int $position): self
    {
        return new self(sprintf(
            'Row %d of `wired` in the wiring envelope says `%s` is `%s`, which this app has no word for.',
            $position,
            $field->value,
            $said,
        ));
    }
}
