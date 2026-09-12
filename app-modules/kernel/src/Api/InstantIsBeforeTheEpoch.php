<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A moment arrived from before 1970, which nothing here produces.
 *
 * Raised where a number becomes an `Instant`. A clock and a stack both answer
 * with a moment after the epoch, so a negative one is a subtraction that went
 * wrong upstream — and carried rather than refused it turns "expired" into
 * "expired fifty years ago" on a screen somebody is reading (C3).
 */
final class InstantIsBeforeTheEpoch extends InvalidArgumentException
{
    public static function of(int $seconds): self
    {
        return new self(sprintf('%d is before the epoch, and nothing here answers with a moment that early.', $seconds));
    }
}
