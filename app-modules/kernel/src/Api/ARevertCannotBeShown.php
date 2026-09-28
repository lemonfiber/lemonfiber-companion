<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * What putting the configuration back reverts arrived with a part that cannot be shown.
 *
 * Refused rather than drawn short, because a preview is what the operator
 * agrees to: a file with no path, a line on neither side, or a connection with
 * no name is a part of the reset they would agree to without being told.
 */
final class ARevertCannotBeShown extends InvalidArgumentException
{
    /** A file was named with nothing. */
    public static function withoutAPath(): self
    {
        return new self('A file putting the configuration back reverts arrived with no path, so nobody could tell which file it changes.');
    }

    /** A line of a diff carries neither the operator's mark nor lemonfiber's. */
    public static function unmarked(int $position): self
    {
        return new self(sprintf(
            'Line %d of a diff is marked as neither the operator\'s nor lemonfiber\'s, so nobody could tell whether it goes or comes back.',
            $position,
        ));
    }

    /** A connection was named with nothing. */
    public static function anUnnamedConnection(): self
    {
        return new self('A connection putting the configuration back reverts arrived with no name, so nobody could tell which one goes back.');
    }
}
