<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A survey of what is already on a machine arrived with a word it owes left blank.
 *
 * Refused rather than shown: a service with no name, or a mode that will not
 * say what it would come to, is one an operator cannot choose about.
 */
final class TheSurveySaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because the survey is long. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A survey of what is already here arrived with its `%s` blank, and nothing can be chosen about a thing that will not say what it is.',
            $field,
        ));
    }
}
