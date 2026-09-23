<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;

use function sprintf;

/**
 * The `bandwidth` envelope did not hold what the contract says it holds.
 *
 * {@see AlertsAreUnreadable}'s refusal, for how the line is shared. A
 * developer reads it, so it is `sprintf` and never translated (`L1`).
 *
 * **A line that could not be read is refused rather than defaulted**, because
 * the default for a missing limit is *unlimited*, and that is the answer
 * somebody with a capped plan would believe.
 */
final class BandwidthIsUnreadable extends InvalidArgumentException
{
    /** A field at the top of the payload, or under the one named. */
    public static function missing(string $where): self
    {
        return new self(sprintf(
            'The bandwidth envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $where,
        ));
    }

    /** One entry of a list of sentences is not a sentence. */
    public static function remark(string $list, int $position): self
    {
        return new self(sprintf(
            'Entry %d of `%s` in the bandwidth envelope is not a sentence. It is refused rather than dropped: a caution one short is a reading trusted further than it should be.',
            $position,
            $list,
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
            'The bandwidth envelope says `%s` is `%s`, and this app reads %s. Drawing it as the nearest one would be a guess about somebody\'s line.',
            $where,
            $said,
            implode(', ', array_map(static fn(string $word): string => sprintf('`%s`', $word), $accepted)),
        ));
    }
}
