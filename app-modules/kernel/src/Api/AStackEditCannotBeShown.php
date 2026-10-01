<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A stack file the operator edited arrived with a part that cannot be shown.
 *
 * Refused rather than drawn short: a file with no path names nothing, and a
 * line on neither side of the diff cannot be placed as the operator's or
 * lemonfiber's.
 */
final class AStackEditCannotBeShown extends InvalidArgumentException
{
    /** A file was named with nothing. */
    public static function withoutAPath(): self
    {
        return new self('A stack file the operator edited arrived with no path, so nobody could tell which file it is.');
    }

    /** A line of a diff carries neither the operator's mark nor lemonfiber's. */
    public static function unmarked(int $position): self
    {
        return new self(sprintf(
            'Line %d of a diff is marked as neither the operator\'s nor lemonfiber\'s, so nobody could tell whose it is.',
            $position,
        ));
    }
}
