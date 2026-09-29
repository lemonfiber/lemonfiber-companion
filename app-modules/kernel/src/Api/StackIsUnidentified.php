<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * Retained state or pairing material named a stack with nothing it can be told apart by.
 *
 * Its own type rather than `KeyIsBlank`, which is about an idempotency key. The
 * two failures read the same in a stack trace and mean opposite things: one is
 * a command the server cannot recognise a retry of, and this one is a stack the
 * app cannot tell from another — the whole subject. A single exception
 * for both would put a catch block in front of two unrelated bugs.
 */
final class StackIsUnidentified extends InvalidArgumentException
{
    public static function inRetainedState(): self
    {
        return new self('Retained state named a stack with a blank identifier, so nothing it holds can be told from another stack\'s.');
    }

    public static function byItsOwnMaterial(): self
    {
        return new self('Pairing material named its stack with something other than the 32 lower-case hexadecimal characters a stack mints, so the machine it describes cannot be told from another.');
    }
}
