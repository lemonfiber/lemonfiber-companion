<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_filter;
use function array_key_exists;
use function array_values;
use function count;
use function in_array;
use function is_array;

use const T_COMMENT;
use const T_CONSTANT_ENCAPSED_STRING;
use const T_DOC_COMMENT;
use const T_STRING;
use const T_WHITESPACE;

/**
 * What a call was handed, read out of its tokens.
 *
 * Its own class rather than a pattern in each rule that needs one, and the
 * reason is the pattern those rules keep reaching for. An argument list is
 * matched as `\([^)]*\)`, which stops at the first `)` — so the call reads
 * correctly for as long as no argument is itself a call, and walks past the
 * whole thing the moment one is. Both readings that wanted this were wrong in
 * that way and wrong invisibly: `C10`'s missed `iterator_to_array($this->of(),
 * preserve_keys: false)`, and `G6`'s could not see past `skip()` to what was
 * inside the brackets at all.
 *
 * Depth is the only thing that reads an argument list correctly, and a depth
 * counter is worth writing once — the bug it hides is an off-by-one in a branch
 * nobody re-reads.
 */
final readonly class Calls
{
    /**
     * The arguments of the call whose name sits at `$at`, one group per argument.
     *
     * Empty where the name is not being called: a method's declaration, a
     * constant, a word in an identifier somewhere. The trailing group a
     * multi-line call's final comma leaves behind is dropped, so the last group
     * is the last argument rather than the whitespace after it.
     *
     * @param list<array{int, string, int}|string> $tokens
     *
     * @return list<list<array{int, string, int}|string>>
     */
    public static function argumentsAt(array $tokens, int $at): array
    {
        if (! array_key_exists($at + 1, $tokens) || $tokens[$at + 1] !== '(') {
            return [];
        }

        return array_values(array_filter(
            self::split(self::insideTheBrackets($tokens, $at + 1)),
            static fn(array $group): bool => self::meaningful($group) !== [],
        ));
    }

    /**
     * Whether a token is that name, written as a name.
     *
     * @param array{int, string, int}|string $token
     *
     * @phpstan-assert-if-true array{int, string, int} $token
     */
    public static function isNamed(array|string $token, string $name): bool
    {
        return is_array($token) && $token[0] === T_STRING && $token[1] === $name;
    }

    /**
     * Whether an argument group is one string literal and nothing else.
     *
     * The question a rule asks when it wants a sentence somebody wrote rather
     * than an expression that happens to mention one: a condition carrying a
     * string of its own — `PHP_OS_FAMILY === 'Darwin'` — holds a literal and
     * says nothing a reader was looking for.
     *
     * @param list<array{int, string, int}|string> $group
     */
    public static function isALoneString(array $group): bool
    {
        $said = self::meaningful($group);

        return count($said) === 1 && is_array($said[0]) && $said[0][0] === T_CONSTANT_ENCAPSED_STRING;
    }

    /**
     * Whether an argument group is `name: value`.
     *
     * The colon is what makes the name an argument rather than a constant, a
     * method or a variable that happens to be spelled the same way.
     *
     * @param list<array{int, string, int}|string> $group
     */
    public static function isNamedArgument(array $group, string $name): bool
    {
        $said = self::meaningful($group);

        return count($said) > 1 && self::isNamed($said[0], $name) && $said[1] === ':';
    }

    /**
     * Those tokens with the whitespace and the comments taken out.
     *
     * @param list<array{int, string, int}|string> $tokens
     *
     * @return list<array{int, string, int}|string>
     */
    public static function meaningful(array $tokens): array
    {
        return array_values(array_filter(
            $tokens,
            static fn(array|string $token): bool => ! is_array($token)
                || ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], strict: true),
        ));
    }

    /**
     * The tokens between the bracket at `$at` and the one that closes it.
     *
     * @param list<array{int, string, int}|string> $tokens
     *
     * @return list<array{int, string, int}|string>
     */
    private static function insideTheBrackets(array $tokens, int $at): array
    {
        $inside = [];
        $depth = 0;
        $total = count($tokens);

        for ($here = $at; $here < $total; $here++) {
            $outside = $depth;
            $depth += self::depthChange($tokens[$here]);

            // The bracket that closes the call ends the list; the one that
            // opened it is not an argument. Everything between them is, nested
            // brackets and all, so that the split below can count depth.
            if ($depth === 0) {
                return $inside;
            }

            if ($outside !== 0) {
                $inside[] = $tokens[$here];
            }
        }

        return $inside;
    }

    /**
     * One argument list split at the commas that separate its arguments.
     *
     * Only a comma at the top of the list separates: one inside a closure's own
     * parameters, an array, or a nested call belongs to that.
     *
     * @param list<array{int, string, int}|string> $tokens
     *
     * @return list<list<array{int, string, int}|string>>
     */
    private static function split(array $tokens): array
    {
        $groups = [[]];
        $depth = 0;

        foreach ($tokens as $token) {
            $depth += self::depthChange($token);

            if ($depth === 0 && $token === ',') {
                $groups[] = [];

                continue;
            }

            $groups[count($groups) - 1][] = $token;
        }

        return $groups;
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
}
