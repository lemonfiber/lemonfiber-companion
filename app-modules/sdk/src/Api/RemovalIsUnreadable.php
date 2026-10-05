<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;

use function sprintf;

/**
 * The `removal` envelope did not hold what the contract says it holds.
 *
 * A developer reads it, so it is `sprintf` and never translated. Refused rather
 * than salvaged: a reach read as the nearest one could show somebody still
 * holding an account as somebody gone.
 */
final class RemovalIsUnreadable extends InvalidArgumentException
{
    /** A field of the envelope is absent, blank, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The removal envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** A word from a closed set this app has no case for. */
    public static function word(NamesAWireField $field, string $said, string ...$accepted): self
    {
        return new self(sprintf(
            'The removal envelope says `%s` is `%s`, and this app reads %s. Drawing it as the nearest one would be a guess about whether somebody is still in the household.',
            $field->value,
            $said,
            implode(', ', array_map(static fn(string $word): string => sprintf('`%s`', $word), $accepted)),
        ));
    }
}
