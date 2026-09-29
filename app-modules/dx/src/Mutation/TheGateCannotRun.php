<?php

declare(strict_types=1);

namespace Dx\Mutation;

use RuntimeException;

/**
 * The mutation gate was asked to run over something it cannot judge honestly.
 *
 * Raised rather than reported and carried on from, because every case is one
 * where carrying on passes over less than it says: a tree with no floor, a
 * group naming a path nothing measures, a plan that names a shard it does not
 * hold. The message is the whole of what a reader gets, so it says what to do.
 */
final class TheGateCannotRun extends RuntimeException
{
    public static function because(string $why): self
    {
        return new self($why);
    }
}
