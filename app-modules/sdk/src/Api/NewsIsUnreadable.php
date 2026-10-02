<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `news-items` envelope did not hold what the contract says it holds.
 *
 * A developer reads it, so it is `sprintf` and never translated (`L1`).
 * Refused rather than salvaged: an item dropped from a list is one the phone
 * would never mark, and an onset guessed at would mark the wrong one.
 */
final class NewsIsUnreadable extends InvalidArgumentException
{
    /** A field of the envelope itself is absent, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The news-items envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One item of a list is not one, or lacks a field its kind always carries. */
    public static function item(NamesAWireField $list, NamesAWireField $field, int $position): self
    {
        return new self(sprintf(
            'Entry %d of `%s` in the news-items envelope has no readable `%s`.',
            $position + 1,
            $list->value,
            $field->value,
        ));
    }
}
