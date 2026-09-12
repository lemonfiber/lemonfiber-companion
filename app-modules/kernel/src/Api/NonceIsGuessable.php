<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A nonce arrived short enough to be searched.
 *
 * Raised where a string becomes a `Nonce`. It is not a smaller nonce — an
 * attacker replaying a command needs only to land on a key the server has
 * already seen, and the search is over whatever length the type permits, so
 * the floor is the promise rather than a preference (C3, B2).
 */
final class NonceIsGuessable extends InvalidArgumentException
{
    public static function at(int $length): self
    {
        return new self(sprintf(
            'A nonce of %d characters is short enough to search; %d is the floor.',
            $length,
            Nonce::SHORTEST,
        ));
    }
}
