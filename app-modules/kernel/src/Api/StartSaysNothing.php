<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A line said while a start runs carried nothing to draw.
 *
 * A start line is the stack telling the operator what the wait is for, so one
 * that says nothing is a payload gone wrong rather than a quiet stack, and it
 * is refused rather than drawn as a blank.
 */
final class StartSaysNothing extends InvalidArgumentException
{
    public static function inItsLine(): self
    {
        return new self('A line said while a start runs was blank or not text, so there is nothing to show of what the start waits for.');
    }
}
