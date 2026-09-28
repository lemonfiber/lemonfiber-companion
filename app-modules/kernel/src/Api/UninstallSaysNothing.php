<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * Taking lemonfiber off arrived with a word it owes left blank, or a figure it cannot have.
 *
 * Refused rather than shown: a line with no name is something the operator
 * would be agreeing to remove without being told what, and a figure below
 * none, or a download further along than finished, is not a figure.
 */
final class UninstallSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because there are many. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'Taking lemonfiber off arrived with its `%s` blank, and a removal that will not say what it reaches is not one to agree to.',
            $field,
        ));
    }

    /** A figure was outside what it can be. */
    public static function outside(string $field, int $said): self
    {
        return new self(sprintf(
            'Taking lemonfiber off arrived with its `%s` at %d, which it cannot be.',
            $field,
            $said,
        ));
    }
}
