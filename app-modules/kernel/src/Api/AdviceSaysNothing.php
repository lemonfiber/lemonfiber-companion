<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A reading of which app to watch on arrived with a word it owes left blank.
 *
 * Refused rather than shown: a device with no name, or a recommendation with
 * no app in it, is advice that reads as given and is not.
 */
final class AdviceSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because the list can be long. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A reading of which app to watch on arrived with its `%s` blank, and advice that will not say it is worse than none.',
            $field,
        ));
    }
}
