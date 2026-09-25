<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;

use function sprintf;

/**
 * The `invitation` envelope did not hold what the contract says it holds.
 *
 * A developer reads it, so it is `sprintf` and never translated (`L1`).
 * Refused rather than salvaged: a standing read as the nearest one could show
 * somebody who has joined as somebody invited, and they would be sent a link
 * to an account they already use.
 */
final class InvitationIsUnreadable extends InvalidArgumentException
{
    /** A field of the envelope itself is absent, blank, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The invitation envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** A field under another is absent, blank, or not what the contract says it is. */
    public static function under(NamesAWireField $parent, NamesAWireField $field): self
    {
        return new self(sprintf(
            'The invitation envelope has no readable `%s`.',
            $field->under($parent),
        ));
    }

    /** A word from a closed set this app has no case for. */
    public static function word(NamesAWireField $field, string $said, string ...$accepted): self
    {
        return new self(sprintf(
            'The invitation envelope says `%s` is `%s`, and this app reads %s. Drawing it as the nearest one would be a guess about whether somebody is in the household.',
            $field->value,
            $said,
            implode(', ', array_map(static fn(string $word): string => sprintf('`%s`', $word), $accepted)),
        ));
    }
}
