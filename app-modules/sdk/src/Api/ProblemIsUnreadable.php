<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;

use function sprintf;

/**
 * The `error` envelope did not hold what the contract says an error holds.
 *
 * Every one of these is a bug somewhere other than here — a server ahead of
 * this app, a proxy rewriting a body, a fixture written by hand — and the
 * message says which field and what arrived, because that is the only thing
 * that shortens the search. A developer reads it, so it is `sprintf` and never
 * translated (L1).
 *
 * It refuses rather than substitutes. The generated envelope asserts its
 * payload's shape without checking it, so what reaches here is whatever came
 * off the socket; filling a missing summary with an empty string would put a
 * heading with no sentence under it on a screen, and blame the app.
 */
final class ProblemIsUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        // One literal rather than a concatenation. A message split across lines
        // is a string built at runtime, and every join in it is a decision no
        // test defends — the mutation run says so by removing one and watching
        // nothing fail.
        return new self(sprintf(
            'The error envelope has no `%s`, or it is not text. Every error the contract describes carries one, so this answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    public static function severity(string $said): self
    {
        return self::word(WireField::Severity->value, $said, array_map(
            static fn(Severity $severity): string => $severity->value,
            Severity::cases(),
        ));
    }

    public static function standing(string $said): self
    {
        return self::word(WireField::State->value, $said, array_map(
            static fn(Standing $standing): string => $standing->value,
            Standing::cases(),
        ));
    }

    public static function remedy(int $position): self
    {
        return new self(sprintf(
            'Remedy %d in the error envelope is not an action. A remedy is one thing to do, phrased as something to do, and a row that is not text renders as a button with no label.',
            $position,
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
            'The error envelope says its %s is `%s`, and this app reads %s. Guessing which of them was meant is how a critical failure gets shown as an advisory.',
            $field,
            $said,
            implode(', ', array_map(static fn(string $word): string => sprintf('`%s`', $word), $read)),
        ));
    }
}
