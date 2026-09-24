<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A reading of the room on a machine that arrived with something it had to say left blank, or below nothing.
 *
 * Refused at construction, because a figure that is not one and a name that is
 * blank both read as an answer to somebody deciding what to remove.
 */
final class RoomSaysNothing extends InvalidArgumentException
{
    /** A word the reading owes was blank. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A reading of the room on this machine arrived with its `%s` blank, and a line that will not say what it is about cannot be weighed.',
            $field,
        ));
    }

    /** A figure the reading owes was below nothing. */
    public static function negative(string $field, int $said): self
    {
        return new self(sprintf(
            'A reading of the room on this machine gave `%s` as %d. Room is never below nothing, so this is not a figure.',
            $field,
            $said,
        ));
    }
}
