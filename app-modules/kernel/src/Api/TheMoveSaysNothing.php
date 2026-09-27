<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * What moving in came to arrived with a word it owes left blank.
 *
 * Refused rather than shown: a move turned away with no reason is one an
 * operator will try again, and a service or a path with no name is one they
 * cannot act on.
 */
final class TheMoveSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because a move says a great deal. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'What moving in came to arrived with its `%s` blank, and an operator cannot act on a thing that will not say what it is.',
            $field,
        ));
    }
}
