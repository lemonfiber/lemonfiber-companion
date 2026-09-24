<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * How the line is shared arrived saying less than it needs to, or something it cannot mean.
 *
 * Refused rather than shown, for {@see RequestSaysNothing}'s reason: a line's
 * state with a blank where *what that means* belongs, or a byte count below
 * zero, is an answer that reads as complete and is not.
 */
final class LineSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because a refusal naming nothing is the defect. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'How the line is shared arrived with its `%s` blank, and a reading of somebody\'s line that will not say it reads as complete.',
            $field,
        ));
    }

    /** A figure that counts bytes arrived below zero, which no measurement produces. */
    public static function negative(string $field, int $said): self
    {
        return new self(sprintf(
            'How the line is shared says its `%s` is %d, and a count of bytes below zero is not a figure anything measured.',
            $field,
            $said,
        ));
    }
}
