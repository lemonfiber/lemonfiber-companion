<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `archives` envelope did not hold what the contract says it holds.
 *
 * Refused rather than read as an empty list, and that is the whole point of
 * it: *no copy has been taken* and *the copies could not be read* are
 * different answers, and the first is the one somebody would believe.
 */
final class ArchivesAreUnreadable extends InvalidArgumentException
{
    public static function missing(WireField $field): self
    {
        return new self(sprintf(
            'The archives envelope has no `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    public static function archive(int $position): self
    {
        return new self(sprintf(
            'Archive %d in the archives envelope is not a name. A copy nobody can name is one nobody could ask to have put back.',
            $position,
        ));
    }
}
