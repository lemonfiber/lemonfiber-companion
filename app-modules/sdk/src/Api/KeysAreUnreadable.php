<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function implode;

use InvalidArgumentException;

use function sprintf;

/**
 * The `keys` envelope did not hold what the contract says it holds.
 *
 * A developer reads it, so it is `sprintf` and never translated. Refused rather
 * than salvaged: a key dropped for being unreadable is one the screen says the
 * stack does not list, and nobody revokes a key they cannot see.
 */
final class KeysAreUnreadable extends InvalidArgumentException
{
    /** A field of the envelope itself is absent, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The keys envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One key's field is absent, blank, or not what the contract says it is. */
    public static function said(NamesAWireField $field, int $position): self
    {
        return new self(sprintf('Entry %d of `keys` in the keys envelope has no readable `%s`.', $position, $field->value));
    }

    /** A word from a closed set this app has no case for. */
    public static function word(NamesAWireField $field, string $said, int $position, string ...$accepted): self
    {
        return new self(sprintf(
            'Entry %d of `keys` in the keys envelope says `%s` is `%s`, and this app reads %s.',
            $position,
            $field->value,
            $said,
            implode(', ', $accepted),
        ));
    }
}
