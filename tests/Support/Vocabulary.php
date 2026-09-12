<?php

declare(strict_types=1);

namespace Tests\Support;

use function file_get_contents;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;
use function str_replace;

use const T_COMMENT;
use const T_CONSTANT_ENCAPSED_STRING;
use const T_DOC_COMMENT;
use const T_MATCH;
use const T_WHITESPACE;

use function token_get_all;
use function trim;

/**
 * Closed sets written as literals, found in the source that writes them.
 *
 * Read over tokens rather than with a regular expression, and that is the whole
 * reason this class exists rather than a `preg_match_all` in the test. Every
 * comment in this codebase quotes code — the paragraph explaining why
 * `$scheme === 'https'` is wrong contains `$scheme === 'https'` — so a text
 * search finds the explanation of the rule before it finds a breach of it, and
 * a rule that fires on its own documentation gets deleted.
 */
final readonly class Vocabulary
{
    /**
     * Every string literal a module compares identity against, and where.
     *
     * @return list<string> `'value' — path:line`, one per comparison
     */
    public static function comparedAgainst(): array
    {
        $found = [];

        foreach (Module::all() as $module) {
            foreach ($module->classes() as $file) {
                $source = file_get_contents($file);

                if (! is_string($source)) {
                    continue;
                }

                foreach (self::inSource($source) as $literal) {
                    $found[] = sprintf(
                        "'%s' — %s:%d",
                        $literal[0],
                        trim(str_replace(Tree::root(), '', $file), '/'),
                        $literal[1],
                    );
                }
            }
        }

        return $found;
    }

    /**
     * A string literal with something in it.
     *
     * `''` is not a vocabulary — it is the emptiness check every value object
     * opens with, a fact about a string rather than a decision about a set.
     *
     * @param array{int, string, int}|string $token
     * @phpstan-assert-if-true array{int, string, int} $token
     */
    public static function isANonEmptyLiteral(array|string $token): bool
    {
        return is_array($token)
            && $token[0] === T_CONSTANT_ENCAPSED_STRING
            && trim($token[1], "'\"") !== '';
    }

    /**
     * The literals compared against in one file's source.
     *
     * Two shapes, because there are two ways to decide a value is one of a set
     * and this rule was written knowing only the first. `===` was the whole of
     * it, and `match ($said) { 'granted', 'denied' => … }` makes exactly the
     * same claim — the same closed set, the same silent fall-through on a
     * misspelling — while producing no comparison token at all. So it went
     * unseen, in the idiom this codebase reaches for first.
     *
     * @return list<array{string, int}>
     */
    private static function inSource(string $source): array
    {
        $tokens = self::worthReading($source);
        $found = [];

        foreach ($tokens as $at => $token) {
            if (self::isAnIdentityComparison($token)) {
                $found = [...$found, ...self::literalsBeside($tokens, $at)];

                continue;
            }

            if (self::isAMatch($token)) {
                $found = [...$found, ...MatchArms::of($tokens, $at)];
            }
        }

        return $found;
    }

    /**
     * A `===` or `!==`, which is one of the two places a vocabulary gets decided.
     *
     * @param array{int, string, int}|string $token
     */
    private static function isAnIdentityComparison(array|string $token): bool
    {
        return is_array($token)
            && in_array($token[0], [T_IS_IDENTICAL, T_IS_NOT_IDENTICAL], strict: true);
    }

    /**
     * The string literals on either side of the token at `$at`.
     *
     * Both sides, because `'https' === $scheme` is the same claim written the
     * other way round and a checker that read one side would be half a rule.
     *
     * @param list<array{int, string, int}|string> $tokens
     * @return list<array{string, int}>
     */
    private static function literalsBeside(array $tokens, int $at): array
    {
        $found = [];

        foreach ([$tokens[$at - 1] ?? null, $tokens[$at + 1] ?? null] as $beside) {
            if ($beside === null || ! self::isANonEmptyLiteral($beside)) {
                continue;
            }

            $found[] = [trim($beside[1], "'\""), $beside[2]];
        }

        return $found;
    }

    /**
     * A `match`, which decides a vocabulary without ever writing `===`.
     *
     * @param array{int, string, int}|string $token
     */
    private static function isAMatch(array|string $token): bool
    {
        return is_array($token) && $token[0] === T_MATCH;
    }

    /**
     * The tokens that carry meaning, with comments and whitespace dropped.
     *
     * Dropping whitespace is what lets the check look one token to each side
     * instead of walking past an unknown amount of formatting.
     *
     * @return list<array{int, string, int}|string>
     */
    private static function worthReading(string $source): array
    {
        $found = [];

        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE], strict: true)) {
                continue;
            }

            $found[] = $token;
        }

        return $found;
    }
}
