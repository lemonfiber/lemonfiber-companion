<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A change to a stack's line that would ask the stack nothing it could act on.
 */
final class LineChangeAsksNothing extends InvalidArgumentException
{
    /** Every part was left as it is. */
    public static function atAll(): self
    {
        return new self('A change to the line was asked with every part left as it is, which is a reading rather than a change.');
    }

    /** A part said to be written with nothing in it. */
    public static function ofThePart(APartOfTheLine $part): self
    {
        return new self(sprintf('The `%s` of a change to the line was written as nothing, which is leaving it as it is.', $part->value));
    }

    /** A lift of fewer than no minutes. */
    public static function forALiftOf(int $minutes): self
    {
        return new self(sprintf('A lift of %d minutes was asked for, and a length cannot be below nothing.', $minutes));
    }
}
