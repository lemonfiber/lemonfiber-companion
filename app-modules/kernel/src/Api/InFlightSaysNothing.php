<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * What a stop would interrupt arrived with a download it does not describe.
 */
final class InFlightSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A download still coming down arrived with its `%s` blank, and one that will not say what it is cannot be named before a stop.',
            $field,
        ));
    }

    /** A figure was outside what it can be. */
    public static function outside(string $field, int $said): self
    {
        return new self(sprintf(
            'A download still coming down arrived with its `%s` at %d, which it cannot be.',
            $field,
            $said,
        ));
    }
}
