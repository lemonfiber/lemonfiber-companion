<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * Retained state named a stack with nothing.
 *
 * Its own type rather than `KeyIsBlank`, which is about an idempotency key. The
 * two failures read the same in a stack trace and mean opposite things: one is
 * a command the server cannot recognise a retry of, and this one is a stack the
 * app cannot tell from another — `N1-R11`'s whole subject. A single exception
 * for both would put a catch block in front of two unrelated bugs.
 */
final class StackIsUnidentified extends InvalidArgumentException
{
    public static function inRetainedState(): self
    {
        return new self('Retained state named a stack with a blank identifier, so nothing it holds can be told from another stack\'s.');
    }
}
