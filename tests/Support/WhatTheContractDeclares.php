<?php

declare(strict_types=1);

namespace Tests\Support;

use function file_get_contents;
use function is_file;
use function preg_match;
use function sprintf;
use function strlen;
use function substr;
use function trim;

/**
 * The generated contract's payload types, read as data rather than as prose.
 *
 * The types the vendored package declares are PHPStan docblocks, which makes
 * them a schema nobody can run. This is the part that makes them one: a
 * balanced-bracket reader over the `@phpstan-type Data` line, so a test can ask
 * what fields a payload may have and which of them a stack always sends.
 *
 * Split from {@see WhatTheContractAccepts} — the pair reads as one sentence — because reading a type and judging a
 * payload against one are different jobs, and together they were more than a
 * class is allowed to hold.
 */
final readonly class WhatTheContractDeclares
{
    /** Where the generated envelopes are written. */
    private const string GENERATED = 'vendor/lemonfiber/sdk-php/src/Generated';

    /**
     * The payload shape one envelope declares, as text.
     *
     * The `@phpstan-type Data` line and nothing else, for the reason the gap
     * register gives about its own reading: every docblock in that package
     * quotes field names in prose, so anything wider reads an explanation as a
     * declaration.
     *
     * An empty string where the contract has no such envelope, which the caller
     * reports — a missing type is a test naming an envelope that does not
     * exist, and silently accepting every payload is the one answer that must
     * not come out of this.
     */
    public static function of(string $envelope): string
    {
        $path = sprintf('%s/%s.php', Tree::at(self::GENERATED), $envelope);

        if (! is_file($path)) {
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

        for ($at = 0; $at < strlen($type); ++$at) {
            $character = $type[$at];

            if ($character === '{' || $character === '<') {
                ++$depth;
            }

            if ($character === '}' || $character === '>') {
                --$depth;
            }

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
}
