<?php

declare(strict_types=1);

namespace Tests\Support;

use function file_exists;
use function file_get_contents;
use function preg_match;
use function sprintf;
use function str_split;
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
    /** Where the generated envelopes are written. */
    private const string GENERATED = 'vendor/lemonfiber/sdk-php/src/Generated';

    /**
     * The payload shape one envelope declares, as text.
     *
     * The `@phpstan-type Data` line and nothing else, for the reason
     * {@see \thePayloadShapeOf()} gives about the gap register: every docblock
     * in that package quotes field names in prose, so anything wider reads an
     * explanation as a declaration.
     *
     * An envelope this repository does not vendor answers with nothing rather
     * than raising, because the caller has a sentence for that and no sentence
     * for a raise coming out of an expectation.
     */
    public static function shapeOf(string $envelope): string
    {
        $path = sprintf('%s/%s.php', Tree::at(self::GENERATED), $envelope);

        // Asked before it is read rather than read under `@`. Suppression is
        // refused here for `G11`'s reason: a warning raised under it is dropped
        // before the result sees it, so the run prints it and still exits zero.
        if (! file_exists($path)) {
            return '';
        }

        $said = file_get_contents($path);

        if ($said === false) {
            return '';
        }

        preg_match('/@phpstan-type Data (.*)/', $said, $shape);

        return trim($shape[1] ?? '');
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
