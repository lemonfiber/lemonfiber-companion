<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A request arrived without a reference a stack could be asked about it by.
 *
 * Refused where the value is built rather than carried to a caller, for
 * {@see RequestWasRefusedForNothing}'s reason: this is a value that cannot be
 * constructed, so there is nothing for a caller to open.
 *
 * A blank reference is what a missing field reads as and what an empty form
 * field sends, and the decision is one where acting on the wrong subject is the
 * whole harm, so it is refused rather than sent and found out about afterwards.
 */
final class RequestIsUnnamed extends InvalidArgumentException
{
    public static function as(string $reference): self
    {
        return new self(sprintf(
            'A request is named by the reference its stack gives it, and "%s" names none, so there is nothing to ask a stack about.',
            $reference,
        ));
    }
}
