<?php

declare(strict_types=1);

namespace Modules\Services\Internal;

use InvalidArgumentException;

use function sprintf;

/**
 * A kept listing opened, and what it held is not a listing in the shape it claims.
 *
 * Raised while a kept listing is read back and caught before the reading
 * returns, where it becomes a listing that is let go of: the stack can always
 * be asked again, so one that does not read is not guessed at.
 */
final class KeptListingDoesNotRead extends InvalidArgumentException
{
    public static function asFields(): self
    {
        return new self('A kept listing of what a stack runs opened to something that is not a set of fields.');
    }

    public static function at(string $field): self
    {
        return new self(sprintf('A kept listing of what a stack runs has no readable "%s".', $field));
    }
}
