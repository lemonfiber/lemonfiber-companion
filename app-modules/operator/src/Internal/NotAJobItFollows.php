<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use LogicException;

use function sprintf;

use UnitEnum;

/**
 * A screen was asked what became of a job followed for something it follows no job for.
 *
 * Raised rather than answered, because any answer would be about another job:
 * the report of the one the screen does follow, drawn where this one was asked.
 */
final class NotAJobItFollows extends LogicException
{
    public static function named(UnitEnum $for): self
    {
        return new self(sprintf('A screen was asked after a job followed for %s::%s, which it follows no job for.', $for::class, $for->name));
    }
}
