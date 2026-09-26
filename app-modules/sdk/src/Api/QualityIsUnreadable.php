<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;

use function sprintf;

/**
 * An answer about quality — the `quality`, `music` or `upgrade` envelope — did not hold what the contract says it holds.
 *
 * One refusal for the three because they are one subject read by one screen,
 * and the kind is named in every message. A developer reads it, so it is
 * `sprintf` and never translated (`L1`). Refused rather than salvaged: a
 * disposition read as the nearest one could call a held choice recorded, and
 * an operator would never be asked to confirm it.
 */
final class QualityIsUnreadable extends InvalidArgumentException
{
    /** A field of the payload itself is absent, blank, or not what the contract says it is. */
    public static function missing(string $kind, NamesAWireField $field): self
    {
        return new self(sprintf(
            'The %s envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $kind,
            $field->value,
        ));
    }

    /** A field under another is absent, blank, or not what the contract says it is. */
    public static function under(string $kind, NamesAWireField $parent, NamesAWireField $field): self
    {
        return new self(sprintf('The %s envelope has no readable `%s`.', $kind, $field->under($parent)));
    }

    /** One entry of a list is not one, or does not say what it owes. */
    public static function entry(string $kind, NamesAWireField $list, NamesAWireField $field, int $position): self
    {
        return new self(sprintf(
            'Entry %d of `%s` in the %s envelope has no readable `%s`. It is refused rather than dropped: a list one row short hides a choice the operator is deciding about.',
            $position,
            $list->value,
            $kind,
            $field->value,
        ));
    }

    /** A word from a closed set this app has no case for. */
    public static function word(string $kind, NamesAWireField $field, string $said, string ...$accepted): self
    {
        return new self(sprintf(
            'The %s envelope says `%s` is `%s`, and this app reads %s. Drawing it as the nearest one would be a guess about what became of a choice.',
            $kind,
            $field->value,
            $said,
            implode(', ', array_map(static fn(string $word): string => sprintf('`%s`', $word), $accepted)),
        ));
    }
}
