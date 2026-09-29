<?php

declare(strict_types=1);

namespace Modules\Health\Internal;

use InvalidArgumentException;

use function sprintf;

/**
 * A kept summary opened, and what it held is not a summary in the shape it claims.
 *
 * Raised while a kept summary is read back and caught before the reading
 * returns, where it becomes a reading that is discarded: a reading can always
 * be read again from the stack, so one that does not read is let go of rather
 * than guessed at. An argument refused, as a kernel value refuses a blank
 * check, because that is what it is: the kept value handed to the reading is
 * not one it can read.
 */
final class KeptSummaryDoesNotRead extends InvalidArgumentException
{
    public static function asFields(): self
    {
        return new self('A kept summary opened to something that is not a set of fields.');
    }

    public static function at(string $field): self
    {
        return new self(sprintf('A kept summary has no readable "%s".', $field));
    }
}
