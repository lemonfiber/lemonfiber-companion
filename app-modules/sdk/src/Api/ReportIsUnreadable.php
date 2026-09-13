<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;

use function sprintf;

/**
 * The `doctor` envelope did not hold what the contract says a report holds.
 *
 * The same refusal `ProblemIsUnreadable` is, for the other payload this module
 * reads, and for the same reason: every one of these is a bug somewhere other
 * than here, and the message says which field and what arrived because that is
 * the only thing that shortens the search. A developer reads it, so it is
 * `sprintf` and never translated (L1).
 *
 * A report is worth refusing rather than salvaging. Half a report is the shape
 * that does damage — nine findings where ten ran reads as a stack with one
 * fewer problem, and nothing on the screen says a row was dropped.
 */
final class ReportIsUnreadable extends InvalidArgumentException
{
    public static function missing(string $field): self
    {
        return new self(sprintf(
            'The doctor envelope has no `%s`, or it is not text. Every report the contract describes carries one, so this answer did not come from a lemonfiber of a version this app can read.',
            $field,
        ));
    }

    public static function finding(int $position): self
    {
        return new self(sprintf(
            'Finding %d in the doctor envelope is not a finding. A report with a row this app cannot read is refused rather than shown one row short, because a report missing a line reads as a stack with one fewer problem.',
            $position,
        ));
    }

    public static function overall(string $said): self
    {
        return self::word('overall', $said, array_map(
            static fn(Overall $overall): string => $overall->value,
            Overall::cases(),
        ));
    }

    public static function category(string $said): self
    {
        return self::word('category', $said, array_map(
            static fn(Category $category): string => $category->value,
            Category::cases(),
        ));
    }

    public static function outcome(string $said): self
    {
        return self::word('verdict.outcome', $said, array_map(
            static fn(Conclusion $conclusion): string => $conclusion->value,
            Conclusion::cases(),
        ));
    }

    public static function severity(string $said): self
    {
        return self::word('verdict.severity', $said, array_map(
            static fn(Severity $severity): string => $severity->value,
            Severity::cases(),
        ));
    }

    public static function standing(string $said): self
    {
        return self::word('verdict.state', $said, array_map(
            static fn(Standing $standing): string => $standing->value,
            Standing::cases(),
        ));
    }

    /** @param list<string> $read the words this app does know, so the gap is visible */
    private static function word(string $field, string $said, array $read): self
    {
        // The accepted list comes from the enum rather than from a sentence
        // written here, so a case added to the contract cannot leave this
        // message describing the old vocabulary.
        return new self(sprintf(
            'The doctor envelope says its %s is `%s`, and this app reads %s. Guessing which of them was meant is how a check that could not run gets shown as one that passed.',
            $field,
            $said,
            implode(', ', array_map(static fn(string $word): string => sprintf('`%s`', $word), $read)),
        ));
    }
}
