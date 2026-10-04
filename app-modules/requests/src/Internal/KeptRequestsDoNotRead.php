<?php

declare(strict_types=1);

namespace Modules\Requests\Internal;

use InvalidArgumentException;

use function sprintf;

/**
 * A kept reading of what the household asked for opened, and what it held is not one in the shape it claims.
 *
 * Raised while a kept reading is read back and caught before it returns,
 * where it becomes a reading that is let go of: the stack can always be asked
 * again, so one that does not read is not guessed at.
 */
final class KeptRequestsDoNotRead extends InvalidArgumentException
{
    public static function asFields(): self
    {
        return new self('A kept reading of what the household asked for opened to something that is not a set of fields.');
    }

    public static function at(string $field): self
    {
        return new self(sprintf('A kept reading of what the household asked for has no readable "%s".', $field));
    }
}
