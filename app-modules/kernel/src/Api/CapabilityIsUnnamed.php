<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * Something says it is about a capability and does not say which.
 *
 * Refused rather than shown, for the reason {@see ServiceIsUnnamed} gives. A
 * capability is the thing a contest is *about*: the sentence on the screen is
 * *two services both claim ▒*, and with the noun missing there is no question
 * left for an operator to answer. Showing the claimants under a blank would ask
 * somebody to choose between two services for a purpose nobody stated.
 */
final class CapabilityIsUnnamed extends InvalidArgumentException
{
    public static function whereOneWasExpected(): self
    {
        return new self(
            'A capability was named as nothing at all, and a contest over nothing is not a question anybody can answer.',
        );
    }
}
