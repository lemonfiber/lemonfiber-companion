<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;

use function sprintf;

/**
 * A `stop-seeding` envelope did not hold what the contract says one holds.
 *
 * Refused rather than read short: an offer missing where the download stands
 * or what goes with it is a removal somebody would agree to without being told
 * what it costs, and a report missing whether it was rehearsed is room that
 * might still be spent reported as freed.
 */
final class StopSeedingIsUnreadable extends InvalidArgumentException
{
    /** A field the answer carries is absent, or not what the contract says it is, at the path named. */
    public static function missing(string $where): self
    {
        return new self(sprintf(
            'The stop-seeding envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $where,
        ));
    }

    /**
     * A word from a closed set this app has no case for.
     *
     * The accepted words are handed in from the enum's own cases, so a case
     * added cannot leave this message describing the old set.
     */
    public static function word(string $where, string $said, string ...$accepted): self
    {
        return new self(sprintf(
            'The stop-seeding envelope says `%s` is `%s`, and this app reads %s. Drawing it as the nearest one would be a guess about what letting it go costs.',
            $where,
            $said,
            implode(', ', array_map(static fn(string $word): string => sprintf('`%s`', $word), $accepted)),
        ));
    }
}
