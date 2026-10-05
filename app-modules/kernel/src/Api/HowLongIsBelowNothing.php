<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A length of time arrived that is less than none.
 *
 * Raised where the seconds become a {@see HowLong}, for
 * {@see SummaryCountsBelowNothing}'s reason: a value that cannot be built
 * rather than a refusal crossing a boundary.
 */
final class HowLongIsBelowNothing extends InvalidArgumentException
{
    public static function seconds(int $seconds): self
    {
        return new self(sprintf('A length of time arrived as %d seconds, which is less than no time.', $seconds));
    }
}
