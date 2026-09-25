<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;

use function sprintf;

/**
 * The `credentials` envelope did not hold what the contract says it holds.
 *
 * A developer reads it, so it is `sprintf` and never translated (`L1`).
 * Refused rather than salvaged: a credential dropped for being unreadable is
 * one the screen says the stack does not hold, and a state read as the nearest
 * one this app knows could draw an invalid credential as a working one.
 */
final class CredentialsIsUnreadable extends InvalidArgumentException
{
    /** A field of the envelope itself is absent, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The credentials envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One entry of the list is not a credential at all. */
    public static function row(int $position): self
    {
        return new self(sprintf(
            'Entry %d of `held` in the credentials envelope is not a credential. It is refused rather than dropped: a list one row short says the stack does not hold something it does.',
            $position,
        ));
    }

    /** One credential's field is absent, blank, or not what the contract says it is. */
    public static function said(NamesAWireField $field, int $position): self
    {
        return new self(sprintf(
            'Entry %d of `held` in the credentials envelope has no readable `%s`.',
            $position,
            $field->value,
        ));
    }

    /** A word from a closed set this app has no case for. */
    public static function word(NamesAWireField $field, string $said, int $position, string ...$accepted): self
    {
        return new self(sprintf(
            'Entry %d of `held` in the credentials envelope says `%s` is `%s`, and this app reads %s. Drawing it as the nearest one would be a guess about whether it works.',
            $position,
            $field->value,
            $said,
            implode(', ', array_map(static fn(string $word): string => sprintf('`%s`', $word), $accepted)),
        ));
    }
}
