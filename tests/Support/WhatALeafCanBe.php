<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_any;
use function get_debug_type;
use function implode;
use function in_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

use Modules\Dx\Internal\WhatTheContractDeclares;

use function preg_match;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function trim;

/**
 * A value the contract declares no fields for, read against what it may be.
 *
 * Split out of {@see WhatTheContractAccepts} because the two answer different
 * questions and only one of them recurses. That class walks a shape: it opens
 * an `array{…}`, finds its fields, and asks about each in turn. This one is
 * where the walking stops — a string, a number, a word out of a closed set —
 * and nothing here calls anything that could lead back up.
 *
 * Keeping them apart is also what keeps either readable. Together they came to
 * thirty-seven of the cognitive complexity a class is allowed thirty of, and
 * the reason was not any one method: it was that following the shape check
 * meant holding the leaf vocabulary in mind at the same time, which is two
 * subjects and one file.
 */
final readonly class WhatALeafCanBe
{
    /** The three ways the notation opens something with parts inside it. */
    private const array THAT_HOLD_OTHERS = ['array{', 'list<', 'array<'];

    /**
     * Whether a declared type is a container rather than a leaf.
     *
     * Asked of the whole declaration rather than of each alternative, because
     * an envelope declaring `array{…}|string` does not arise and reading one as
     * a leaf would be worse than saying so.
     */
    public static function containsOthers(string $type): bool
    {
        return array_any(self::THAT_HOLD_OTHERS, fn(string $opens): bool => str_starts_with(trim($type), $opens));
    }

    /**
     * What is wrong with one value read against the leaf type declared for it.
     *
     * The same *any one of them is enough* rule a shape check uses: a union is
     * satisfied by whichever alternative fits, and a value is only wrong when
     * none does.
     *
     * @return list<string>
     */
    public static function whatDoesNotFit(string $type, mixed $value, string $path): array
    {
        foreach (WhatTheContractDeclares::alternatives($type) as $alternative) {
            if (self::itIsThat(trim($alternative), $value)) {
                return [];
            }
        }

        return [sprintf(
            '`%s` holds %s and the contract declares `%s` there.',
            $path,
            get_debug_type($value),
            trim($type),
        )];
    }

    /**
     * A word read against a type that may be a closed set.
     *
     * The half a shape check cannot do without, and the half that names the
     * defect rather than a symptom of it. `Standings` read the `update`
     * payload's top-level `state` for the *current, pending, stale* triple,
     * which the contract puts under `changelog`; the top-level field exists and
     * answers a different question, so no key was unknown and no key was
     * missing there. What was wrong was the word: `pending` is not one of the
     * five the contract allows at that path.
     *
     * Only closed sets are checked. A field typed `string` or `int` accepts
     * whatever a fixture wants to say, and what it says is the reader's business
     * and the suite's — this asks only whether the stack could have said it.
     *
     * @return list<string>
     */
    public static function outsideItsSet(string $type, mixed $value, string $path): array
    {
        $allowed = self::theWordsAllowedBy($type);

        if ($allowed === [] || ! is_string($value) || in_array($value, $allowed, strict: true)) {
            return [];
        }

        return [sprintf(
            '`%s` says `%s`, and the contract allows only `%s` there.',
            $path,
            $value,
            implode('`, `', $allowed),
        )];
    }

    /**
     * The words a closed set permits, and nothing for a type that is not one.
     *
     * All or nothing: one alternative that is not a quoted literal means the
     * type is open — `'a'|'b'|string` accepts anything — and reporting the two
     * literals as the permitted set would refuse every value the third arm
     * allows.
     *
     * @return list<string>
     */
    private static function theWordsAllowedBy(string $type): array
    {
        $allowed = [];

        foreach (WhatTheContractDeclares::alternatives($type) as $alternative) {
            if (preg_match("/^'(.*)'$/s", $alternative, $said) !== 1) {
                return [];
            }

            $allowed[] = $said[1];
        }

        return $allowed;
    }

    /** Whether one value is of one leaf type. */
    private static function itIsThat(string $type, mixed $value): bool
    {
        if (str_starts_with($type, "'") && str_ends_with($type, "'")) {
            return $value === trim($type, "'");
        }

        return match ($type) {
            'string' => is_string($value),
            // An integer where a float is declared, because JSON writes `1.0`
            // as `1` and a reader taking the number would be right to.
            'float' => is_float($value) || is_int($value),
            'int' => is_int($value),
            'bool' => is_bool($value),
            'true' => $value === true,
            'false' => $value === false,
            'null' => $value === null,
            'mixed' => true,
            default => false,
        };
    }
}
