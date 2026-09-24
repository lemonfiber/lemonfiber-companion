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
    public static function missing(WireField $field): self
    {
        return new self(sprintf(
            'The doctor envelope has no `%s`, or it is not text. Every report the contract describes carries one, so this answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /**
     * One field of one finding, naming both.
     *
     * Separate from {@see missing()} because they are different failures, and
     * the difference is what a reader does next. `missing()` says the doctor
     * envelope has no such field, which is true of an envelope and false of a
     * row — a report of nine findings whose fourth has no verdict was reporting
     * as an answer from an unreadable version of lemonfiber, and the position,
     * the one thing that makes it findable, was dropped on the way.
     */
    public static function inFinding(int $position, WireField $field): self
    {
        return new self(sprintf(
            'Finding %d has no `%s`, or it is not what the contract says it is. A report with a row this app cannot read is refused rather than shown one row short.',
            $position,
            $field->value,
        ));
    }

    /**
     * One finding could not say who put its check there.
     *
     * Refused with the rest of the report rather than shown unattributed: a
     * plugin's check whose origin is lost reads as the stack's own, and a red
     * row the operator then goes looking for in the wrong place.
     */
    public static function origin(int $position, OriginIsUnreadable $why): self
    {
        return new self(sprintf(
            'Finding %d cannot say where its check came from. %s',
            $position,
            $why->getMessage(),
        ), previous: $why);
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
        return self::word(WireField::Overall->value, $said, array_map(
            static fn(Overall $overall): string => $overall->value,
            Overall::cases(),
        ));
    }

    public static function category(string $said): self
    {
        return self::word(WireField::Category->value, $said, array_map(
            static fn(Category $category): string => $category->value,
            Category::cases(),
        ));
    }

    public static function outcome(string $said): self
    {
        return self::word(WireField::Outcome->under(WireField::Verdict), $said, array_map(
            static fn(Conclusion $conclusion): string => $conclusion->value,
            Conclusion::cases(),
        ));
    }

    public static function severity(string $said): self
    {
        return self::word(WireField::Severity->under(WireField::Verdict), $said, array_map(
            static fn(Severity $severity): string => $severity->value,
            Severity::cases(),
        ));
    }

    public static function standing(string $said): self
    {
        return self::word(WireField::State->under(WireField::Verdict), $said, array_map(
            static fn(Standing $standing): string => $standing->value,
            Standing::cases(),
        ));
    }

    /** @param list<string> $read the words this app does know, so the gap is visible */
    private static function word(string $field, string $said, array $read): self
    {
        // The accepted list comes from the enum rather than from a sentence
        // written here, so a case added to the contract cannot leave this
        // message describing the old vocabulary. The field's own name comes
        // from `WireField` for the same reason, one level up.
        return new self(sprintf(
            'The doctor envelope says its %s is `%s`, and this app reads %s. Guessing which of them was meant is how a check that could not run gets shown as one that passed.',
            $field,
            $said,
            implode(', ', array_map(static fn(string $word): string => sprintf('`%s`', $word), $read)),
        ));
    }
}
