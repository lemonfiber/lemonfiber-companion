<?php

declare(strict_types=1);

namespace Tests\Support;

use function count;
use function in_array;
use function is_array;

use const T_DOUBLE_ARROW;

use function trim;

/**
 * The literals a `match` decides on, read out of its tokens.
 *
 * Its own class rather than three more methods on {@see Vocabulary}, because
 * they are a different job: that one finds the files and the comparisons, this
 * one understands one construct's shape. Together they went past the complexity
 * gate, which was the signal rather than the obstacle — a token walker that
 * tracks brace depth wants reading on its own, since the bug that hides in it
 * is a counter off by one in a branch nobody re-reads.
 *
 * **Why a `match` needs reading at all.** `D4` was written around `===` and
 * `!==`, and `match ($said) { 'granted', 'denied' => … }` makes exactly the
 * same claim — the same closed set, the same silent fall to `default` on a
 * misspelling — while producing no comparison token whatsoever. So the rule
 * held everywhere except the idiom this codebase reaches for first.
 */
final readonly class MatchArms
{
    /**
     * The string literals standing as arms of the match beginning at `$at`.
     *
     * Two passes rather than one, because the job is two jobs: find where the
     * match body is, then read the arms inside it. Written as a single loop it
     * tracked brace depth and arm side at once, which the complexity gate
     * refused — rightly, since the bug that would hide in it is a depth counter
     * that is off by one in a branch nobody re-reads.
     *
     * @param list<array{int, string, int}|string> $tokens
     * @return list<array{string, int}>
     */
    public static function of(array $tokens, int $at): array
    {
        return self::conditionLiteralsIn(self::bodyOfMatch($tokens, $at));
    }

    /**
     * The tokens inside one match's braces, each with its depth relative to them.
     *
     * Depth 1 is arm level. Anything deeper belongs to an arm's body — a call,
     * an array, a nested match — and a nested match is reached on its own
     * `T_MATCH` in the outer walk, so nothing is missed by ignoring it here.
     *
     * @param list<array{int, string, int}|string> $tokens
     * @return list<array{array{int, string, int}|string, int}>
     */
    private static function bodyOfMatch(array $tokens, int $at): array
    {
        $body = [];
        $depth = 0;
        $total = count($tokens);

        // Everything before the first `{` is the subject — `match ($said)`. Its
        // parentheses open and close before the body does, so a walk that
        // counted them would see depth return to zero and stop before reading a
        // single arm. That was this checker's first bug, and it was invisible:
        // the rule passed, on the file it was written for. `tests/Guards` now
        // plants a `match` for exactly that reason.
        $here = self::opensAt($tokens, $at);

        for (; $here < $total; $here++) {
            $depth += self::depthChange($tokens[$here]);

            if ($depth === 0) {
                break;
            }

            if (self::isABracket($tokens[$here])) {
                continue;
            }

            $body[] = [$tokens[$here], $depth];
        }

        return $body;
    }

    /**
     * Where the match's body begins — the index of its opening brace.
     *
     * @param list<array{int, string, int}|string> $tokens
     */
    private static function opensAt(array $tokens, int $at): int
    {
        $total = count($tokens);

        for ($here = $at; $here < $total; $here++) {
            if ($tokens[$here] === '{') {
                return $here;
            }
        }

        return $total;
    }

    /**
     * What one token does to the nesting depth.
     *
     * @param array{int, string, int}|string $token
     */
    private static function depthChange(array|string $token): int
    {
        if (in_array($token, ['{', '(', '['], strict: true)) {
            return 1;
        }

        return in_array($token, ['}', ')', ']'], strict: true) ? -1 : 0;
    }

    /**
     * Whether this token is structure rather than content.
     *
     * @param array{int, string, int}|string $token
     */
    private static function isABracket(array|string $token): bool
    {
        return self::depthChange($token) !== 0;
    }

    /**
     * The literals on the condition side of each arm.
     *
     * Only the conditions, never the bodies. `match ($code) { 404 => 'not
     * found' }` decides on `404` and *answers* with a sentence, and a checker
     * reading both would report every message in the codebase as a vocabulary —
     * which is the surest way to get a rule switched off.
     *
     * @param list<array{array{int, string, int}|string, int}> $body
     * @return list<array{string, int}>
     */
    private static function conditionLiteralsIn(array $body): array
    {
        $found = [];
        $inACondition = true;

        foreach ($body as [$token, $depth]) {
            if ($depth !== 1) {
                continue;
            }

            // A condition ends at `=>`; the next `,` at arm level starts one.
            if (is_array($token) && $token[0] === T_DOUBLE_ARROW) {
                $inACondition = false;

                continue;
            }

            if ($token === ',') {
                $inACondition = true;

                continue;
            }

            if ($inACondition && Vocabulary::isANonEmptyLiteral($token)) {
                $found[] = [trim($token[1], "'\""), $token[2]];
            }
        }

        return $found;
    }
}
