<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;

use function sprintf;

/**
 * The `settings` envelope did not hold what the contract says it holds.
 *
 * Refused rather than salvaged: a setting dropped for being unreadable is one
 * the operator cannot see and so cannot change.
 */
final class SettingsAreUnreadable extends InvalidArgumentException
{
    /** A field at the top of the payload, or under the one named. */
    public static function missing(string $where): self
    {
        return new self(sprintf('The settings envelope has no readable `%s`, so it did not come from a lemonfiber this app can read.', $where));
    }

    /** A word from a closed set this app has no case for, with the words it reads. */
    public static function word(string $where, string $said, string ...$accepted): self
    {
        return new self(sprintf(
            'The settings envelope says `%s` is `%s`, and this app reads %s.',
            $where,
            $said,
            implode(', ', array_map(static fn(string $word): string => sprintf('`%s`', $word), $accepted)),
        ));
    }
}
