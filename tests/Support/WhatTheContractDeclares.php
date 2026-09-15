<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_unique;
use function array_values;
use function file_exists;
use function file_get_contents;
use function preg_match;
use function sprintf;
use function str_split;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;

/**
 * The shape a generated envelope declares, read as the notation it is written
 * in rather than as the payload it describes.
 *
 * The half of {@see WhatTheContractAccepts} that never looks at a payload. One
 * class knows what `array{a: 'x'|'y', b?: list<…>}` means and the other knows
 * what to do about a fixture that disagrees with it, and keeping them apart is
 * what stops a change to either being a change to both — the notation is the
 * SDK generator's, and what a suite does about a mismatch is this repository's.
 *
 * Nothing here is specific to a contract. It is a type-notation reader, which
 * is why none of its methods mention a stack, a fixture or a complaint.
 */
final readonly class WhatTheContractDeclares
{
    /**
     * How an entry of a list or a map is written into a path.
     *
     * One marker for both kinds, and public because the two sides of a path
     * comparison have to spell it the same way: {@see WhatTheReadersRead}
     * recovers the reader's half and this declares the contract's, and a marker
     * written out in each would be one edit away from two vocabularies that
     * never match.
     */
    public const string EACH = '[]';

    /**
     * Where the generated envelopes are written.
     *
     * Public because `G12` reads the same directory for a different question —
     * which envelope declares which kind — and a second spelling of the path
     * would be a second thing to move the day the package is laid out
     * differently, with only one of them raising when it was missed.
     */
    public const string GENERATED = 'vendor/lemonfiber/sdk-php/src/Generated';

    /**
     * The payload shape one envelope declares, as text.
     *
     * The `@phpstan-type Data` line and nothing else, for the reason
     * {@see \thePayloadShapeOf()} gives about the gap register: every docblock
     * in that package quotes field names in prose, so anything wider reads an
     * explanation as a declaration.
     */
    public static function shapeOf(string $envelope): string
    {
        return self::whatIsDeclared(sprintf('%s.php', $envelope), '/@phpstan-type Data (.*)/');
    }

    /**
     * The kind one envelope reads, as the word that kind is on the wire.
     *
     * Two hops, because the package keeps them apart: the envelope names a case
     * of the generated enum, and the enum holds the word. Read rather than
     * mapped, for the reason every other reading here is: a map written beside
     * this one is a second copy of the contract's answer, and the copy is what
     * goes on agreeing after the contract has moved.
     */
    public static function kindOf(string $envelope): string
    {
        $case = self::whatIsDeclared(
            sprintf('%s.php', $envelope),
            '/public const Kind KIND = Kind::([A-Za-z0-9_]+);/',
        );

        return $case === ''
            ? ''
            : self::whatIsDeclared('Kind.php', sprintf('/case %s = \'([^\']+)\';/', $case));
    }

    /**
     * The fields one `array{…}` declares, and whether each is required.
     *
     * @return array<string, array{0: bool, 1: string}>
     */
    public static function fieldsOf(string $type): array
    {
        $fields = [];

        foreach (self::split(self::inside($type, 'array{'), ',') as $part) {
            if (preg_match('/^([A-Za-z0-9_]+)(\??):\s*(.*)$/s', $part, $said) !== 1) {
                continue;
            }

            $fields[$said[1]] = [$said[2] === '?', trim($said[3])];
        }

        return $fields;
    }

    /**
     * Every path a type declares, all the way down, in the order it declares them.
     *
     * A field is not `detail`, it is `findings[].verdict.remedies[].detail` —
     * and the difference is the whole of what a name-based reading gets wrong.
     * Nine envelopes declare two hundred and twenty-three of these between
     * them, and forty-one of the names are shared by two paths or more.
     *
     * Both bracket kinds become one marker, `[]`, for the reason
     * {@see WhatTheReadersRead} gives about a reader walking either with
     * `foreach`: what a path is compared against has to be written the way the
     * reader's side of it can be recovered.
     *
     * A union contributes every arm, because the contract writes a tagged shape
     * as one — `verdict` carries `reason` on two arms and `remedies` on two
     * others, and a reading that took the first arm would call the rest fields
     * nobody has.
     *
     * @return list<string>
     */
    public static function everyPathIn(string $type, string $under = ''): array
    {
        $found = [];

        foreach (self::alternatives($type) as $alternative) {
            $found = [...$found, ...self::pathsUnder($alternative, $under)];
        }

        return array_values(array_unique($found));
    }

    /**
     * What sits between a type's opening bracket and the one that closes it.
     *
     * The closing bracket is the last character of a well-formed type, which is
     * what {@see split()} guarantees about every part it hands back.
     */
    public static function inside(string $type, string $opens): string
    {
        return trim(substr($type, strlen($opens), -1));
    }

    /**
     * One type's alternatives, where it is a union.
     *
     * @return list<string>
     */
    public static function alternatives(string $type): array
    {
        return self::split($type, '|');
    }

    /**
     * A type split on a separator that is not inside a bracket.
     *
     * Depth counted over both kinds, because the contract nests them through
     * each other — `array<string, array{…}>` has a comma inside angle brackets
     * and another inside braces, and a split that read either would cut a field
     * in half and report the halves as fields nobody has.
     *
     * @return list<string>
     */
    public static function split(string $type, string $on): array
    {
        $parts = [];
        $held = '';
        $depth = 0;

        foreach (str_split($type) as $character) {
            $depth += self::deeper($character);

            if ($character === $on && $depth === 0) {
                $parts[] = trim($held);
                $held = '';

                continue;
            }

            $held .= $character;
        }

        $parts[] = trim($held);

        return $parts;
    }

    /**
     * The one thing a pattern picks out of a file under the generated tree.
     *
     * An envelope this repository does not vendor answers with nothing rather
     * than raising, because the caller has a sentence for that and no sentence
     * for a raise coming out of an expectation.
     */
    private static function whatIsDeclared(string $file, string $pattern): string
    {
        preg_match($pattern, self::whatIsWrittenIn($file), $said);

        return trim($said[1] ?? '');
    }

    /** What one file under the generated tree says. */
    private static function whatIsWrittenIn(string $file): string
    {
        $path = sprintf('%s/%s', Tree::at(self::GENERATED), $file);

        // Asked before it is read rather than read under `@`. Suppression is
        // refused here for `G11`'s reason: a warning raised under it is dropped
        // before the result sees it, so the run prints it and still exits zero.
        if (! file_exists($path)) {
            return '';
        }

        $said = file_get_contents($path);

        return $said === false ? '' : $said;
    }

    /**
     * One arm of a type, as the paths it puts under a path.
     *
     * @return list<string>
     */
    private static function pathsUnder(string $type, string $under): array
    {
        if (str_starts_with($type, 'list<')) {
            return self::everyPathIn(self::inside($type, 'list<'), sprintf('%s%s', $under, self::EACH));
        }

        if (str_starts_with($type, 'array<')) {
            $holds = self::split(self::inside($type, 'array<'), ',');

            return self::everyPathIn($holds[1] ?? '', sprintf('%s%s', $under, self::EACH));
        }

        if (! str_starts_with($type, 'array{')) {
            return [];
        }

        $found = [];

        foreach (self::fieldsOf($type) as $name => $field) {
            $path = $under === '' ? $name : sprintf('%s.%s', $under, $name);
            $found = [...$found, $path, ...self::everyPathIn($field[1], $path)];
        }

        return $found;
    }

    /**
     * What one character does to the depth, counting both kinds as one.
     *
     * Both kinds together rather than a count each, because the contract nests
     * them through each other and nothing here asks which bracket it is inside
     * — only whether it is inside one.
     */
    private static function deeper(string $character): int
    {
        return match ($character) {
            '{', '<' => 1,
            '}', '>' => -1,
            default => 0,
        };
    }
}
