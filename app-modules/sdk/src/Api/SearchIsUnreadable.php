<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;

use function sprintf;

/**
 * An `askable` or an `asked` envelope did not hold what the contract says one holds.
 *
 * Refused rather than read short: a found title with no id cannot be asked for,
 * and an ask with no sentence would leave the member guessing what it came to.
 */
final class SearchIsUnreadable extends InvalidArgumentException
{
    /** A field the answer carries is absent, or not what the contract says it is, at the path named. */
    public static function missing(string $where): self
    {
        return new self(sprintf(
            'The search or ask answer has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $where,
        ));
    }

    /** A word from a closed set this app has no case for, with the words it reads. */
    public static function word(string $where, string $said, string ...$accepted): self
    {
        return new self(sprintf(
            'The search or ask answer says `%s` is `%s`, and this app reads %s. Drawing it as the nearest one would be a guess about the title.',
            $where,
            $said,
            implode(', ', array_map(static fn(string $word): string => sprintf('`%s`', $word), $accepted)),
        ));
    }
}
