<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * Bytes arrived to be a key and were not thirty-two of them.
 *
 * Raised where bytes become a {@see KeyMaterial}. A key of another length is
 * not a weaker key: the cipher refuses it outright, and it would do so at the
 * moment something is being sealed rather than where the key was made.
 *
 * The message names the length and never the bytes, because a message is what
 * a stack trace carries and a stack trace is what reaches a diagnostic report.
 */
final class KeyIsTheWrongLength extends InvalidArgumentException
{
    public static function at(int $length): self
    {
        return new self(sprintf(
            'A key of %d bytes is not a key; %d is the only length there is.',
            $length,
            KeyMaterial::BYTES,
        ));
    }
}
