<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;

use function sprintf;

/**
 * The `space` envelope did not hold what the contract says it holds.
 *
 * {@see BandwidthIsUnreadable}'s refusal, for how full a machine is. A
 * developer reads it, so it is `sprintf` and never translated (`L1`).
 *
 * **A reading that could not be read is refused rather than defaulted**,
 * because the default for a missing level is a comfortable one, and that is
 * the answer somebody with a full disk would believe.
 */
final class SpaceIsUnreadable extends InvalidArgumentException
{
    /** A field at the top of the payload, or at the path named. */
    public static function missing(string $where): self
    {
        return new self(sprintf(
            'The space envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $where,
        ));
    }

    /** One entry of a list is not an entry. */
    public static function row(string $list, int $position): self
    {
        return new self(sprintf(
            'Entry %d of `%s` in the space envelope is not an entry. It is refused rather than dropped: a list one row short says the room went somewhere it did not.',
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
            'The space envelope says `%s` is `%s`, and this app reads %s. Drawing it as the nearest one would be a guess about somebody\'s disk.',
            $where,
            $said,
            implode(', ', array_map(static fn(string $word): string => sprintf('`%s`', $word), $accepted)),
        ));
    }
}
