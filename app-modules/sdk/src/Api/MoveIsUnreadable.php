<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * An answer about moving in — the `adoption`, `import`, `beside` or `replacement` envelope — did not hold what the contract says it holds.
 *
 * One refusal for the four because they are one subject read by one screen,
 * and the kind is named in every message. A developer reads it, so it is
 * `sprintf` and never translated (`L1`). Refused rather than salvaged: a
 * stance read as the nearest one could call a move that did nothing applied,
 * and a list one row short hides something that did not come across.
 */
final class MoveIsUnreadable extends InvalidArgumentException
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

    /** One entry of a list is not one, or does not say what it owes. */
    public static function entry(string $kind, NamesAWireField $list, int $position, NamesAWireField $field): self
    {
        return new self(sprintf(
            'Entry %d of `%s` in the %s envelope has no readable `%s`. It is refused rather than dropped: a move shown one row short hides something it did or did not do.',
            $position,
            $list->value,
            $kind,
            $field->value,
        ));
    }
}
