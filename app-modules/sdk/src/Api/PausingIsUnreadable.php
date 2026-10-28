<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;
use Modules\Sdk\Internal\TheWordsItReads;

use function sprintf;

/**
 * A `pausing` envelope did not hold what the contract says one holds.
 *
 * Refused rather than read short: a rehearsal missing its offer is a pause
 * nobody can agree to, and a client missing what it said is one the operator
 * would be told was paused, or was not, on a guess.
 */
final class PausingIsUnreadable extends InvalidArgumentException
{
    /** A field the answer carries is absent, or not what the contract says it is, at the path named. */
    public static function missing(string $where): self
    {
        return new self(sprintf(
            'The pausing envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
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
            'The pausing envelope says `%s` is `%s`, and this app reads %s. Drawing it as the nearest one would be a guess about what the downloads are doing.',
            $where,
            $said,
            TheWordsItReads::quoted(...$accepted),
        ));
    }
}
