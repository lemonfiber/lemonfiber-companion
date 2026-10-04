<?php

declare(strict_types=1);

namespace Modules\Updates\Internal;

use InvalidArgumentException;

use function sprintf;

/**
 * A kept reading opened, and what it held is not a reading in the shape it claims.
 *
 * Raised while a kept reading is read back and caught before the reading
 * returns, where it becomes a reading that is let go of: the stack can always
 * be asked again, so one that does not read is not guessed at. An argument
 * refused, as a kernel value refuses a blank version, because that is what it
 * is: the kept value handed to the reading is not one it can read.
 */
final class KeptUpkeepDoesNotRead extends InvalidArgumentException
{
    public static function asFields(): self
    {
        return new self('A kept reading of where a stack stands opened to something that is not a set of fields.');
    }

    public static function at(string $field): self
    {
        return new self(sprintf('A kept reading of where a stack stands has no readable "%s".', $field));
    }
}
