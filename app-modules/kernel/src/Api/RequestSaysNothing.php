<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A connection arrived saying less than a privacy inventory needs.
 *
 * Refused rather than shown, for {@see OriginSaysNothing}'s reason one surface
 * along. What leaves somebody's machine is asked by the person most likely to
 * check the answer, and a row with a blank where *what it sends* or *what
 * switches it off* belongs is an inventory entry that reads as complete and
 * is not.
 */
final class RequestSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because the list can be long. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A connection arrived with its `%s` blank, and an entry in a list of what leaves a machine that will not say it is worse than none: it reads as complete to the person checking.',
            $field,
        ));
    }
}
