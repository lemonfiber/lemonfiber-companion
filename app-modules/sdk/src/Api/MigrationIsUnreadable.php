<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `migration` envelope did not hold what the contract says it holds.
 *
 * A developer reads it, so it is `sprintf` and never translated (`L1`).
 * Refused rather than salvaged: a survey one row short hides a service that
 * is already running, and an operator would choose a mode without it.
 */
final class MigrationIsUnreadable extends InvalidArgumentException
{
    /** A field of the envelope itself is absent, blank, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The migration envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** A field under another is absent, blank, or not what the contract says it is. */
    public static function under(NamesAWireField $parent, NamesAWireField $field): self
    {
        return new self(sprintf(
            'The migration envelope has no readable `%s`.',
            $field->under($parent),
        ));
    }

    /** One entry of a list is not one, or does not say what it owes. */
    public static function entry(NamesAWireField $list, int $position, NamesAWireField $field): self
    {
        return new self(sprintf(
            'Entry %d of `%s` in the migration envelope has no readable `%s`. It is refused rather than dropped: a survey one row short hides something already on the machine.',
            $position,
            $list->value,
            $field->value,
        ));
    }

    /** One service of a project already here is not one, or does not say what it owes. */
    public static function service(int $project, int $position, NamesAWireField $field): self
    {
        return new self(sprintf(
            'Service %d of project %d in the migration envelope has no readable `%s`. It is refused rather than dropped: a project shown one service short hides one that is already running.',
            $position,
            $project,
            $field->value,
        ));
    }
}
