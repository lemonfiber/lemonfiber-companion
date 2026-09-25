<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Modules\Kernel\Api\TheTraceSaysNothing;

use function sprintf;

/**
 * The `trace` envelope did not hold what the contract says it holds.
 *
 * A developer reads it, so it is `sprintf` and never translated (`L1`).
 */
final class TraceIsUnreadable extends InvalidArgumentException
{
    /** A field is absent, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The trace envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** A word from a closed set this app has no case for. */
    public static function word(NamesAWireField $field, string $said, string ...$accepted): self
    {
        return new self(sprintf(
            'The trace envelope says `%s` is `%s`, and this app reads %s.',
            $field->value,
            $said,
            implode(', ', array_map(static fn(string $word): string => sprintf('`%s`', $word), $accepted)),
        ));
    }

    /** Something the kernel refuses: a blank sentence, or a count that cannot be. */
    public static function because(TheTraceSaysNothing $why): self
    {
        return new self(sprintf('The trace envelope holds something that cannot be: %s', $why->getMessage()), previous: $why);
    }
}
