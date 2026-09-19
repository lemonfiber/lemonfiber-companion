<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A sentence arrived with nothing in it for the member to read.
 *
 * Raised where a string becomes a {@see Sentence}, for {@see RemedySaysNothing}'s
 * reason: this is a value that cannot be constructed rather than a refusal
 * crossing a boundary, so there is nothing for a caller to open (C1, C3).
 */
final class SentenceSaysNothing extends InvalidArgumentException
{
    public static function toAMember(): self
    {
        return new self('A sentence arrived with nothing in it, and a blank line reads as something nobody wrote.');
    }
}
