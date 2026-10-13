<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;

use function sprintf;

/**
 * An `alert` event on the stream that this app cannot read.
 *
 * Refused rather than shown as something else: a notification about the
 * wrong thing, or a resolution read as an onset, is worse than none.
 */
final class AlertIsUnreadable extends InvalidArgumentException
{
    /** A field it needs is missing or blank. */
    public static function missing(string $where): self
    {
        return new self(sprintf(
            'An alert on the stream has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $where,
        ));
    }

    /** A word it has no case for. */
    public static function word(string $where, string $said, string ...$accepted): self
    {
        return new self(sprintf(
            'An alert on the stream says `%s` is `%s`, and this app reads %s. Reading it as the nearest one would tell the operator the wrong way it went.',
            $where,
            $said,
            implode(', ', array_map(static fn(string $word): string => sprintf('`%s`', $word), $accepted)),
        ));
    }
}
