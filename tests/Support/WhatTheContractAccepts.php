<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_key_exists;
use function count;
use function file_get_contents;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;

/**
 * A payload read against the shape the vendored contract declares for it.
 *
 * Every contract suite here stands a hand-written payload in for a stack, and a
 * hand-written payload is written by whoever wrote the reader. When the two
 * agree about a field that is not there, both are wrong in the same direction
 * and the suite is green — which is the one failure a contract test cannot
 * survive, because a contract test is the only thing standing between a reader
 * and a machine nobody has run it against.
 *
 * That is not hypothetical. {@see \Modules\Sdk\Api\Standings} read `state` and
 * `running` off the top of the `update` payload, where the contract puts the
 * first under `changelog` and calls the top-level one something else entirely.
 * The fixture put them where the reader looked, three rules passed, and the
 * screen would have refused every stack with an update waiting.
 *
 * So the fixture is checked against the contract rather than against the
 * reader. What is asserted is both directions: a key the contract does not have
 * at that path, and a key it requires that the payload leaves out. The second
 * matters as much as the first — a fixture short of a required field is a
 * sample of a payload no stack sends, and a reader tested only against it has
 * been tested against nothing.
 *
 * Only the shapes matter. What a field *says* is the reader's business and the
 * suites assert it; this asks whether the field is one the stack would send.
 */
final readonly class WhatTheContractAccepts
{
    /** Where the generated envelopes are written. */
    private const string GENERATED = 'vendor/lemonfiber/sdk-php/src/Generated';

    /**
     * Everything about a payload the contract would not recognise.
     *
     * The body as it goes on the wire, so the `kind` is checked too: a fixture
     * carrying one envelope's payload under another's name is a sample of a
     * conversation that cannot happen.
     *
     * @param  array<array-key, mixed>  $body
     * @return list<string>
     */
    public static function complaintsAbout(string $envelope, array $body): array
    {
        $shape = self::shapeOf($envelope);

        if ($shape === '') {
            return [sprintf('The contract has no envelope called `%s`.', $envelope)];
        }

        $data = $body['data'] ?? null;

        if (! is_array($data)) {
            return [sprintf('`%s` was stood in for by a body with no `data`.', $envelope)];
        }

        return self::against($shape, $data, 'data');
    }

    /**
     * The payload shape one envelope declares, as text.
     *
     * The `@phpstan-type Data` line and nothing else, for the reason
     * {@see \thePayloadShapeOf()} gives about the gap register: every docblock
     * in that package quotes field names in prose, so anything wider reads an
     * explanation as a declaration.
     */
    private static function shapeOf(string $envelope): string
    {
        $path = Tree::at(self::GENERATED) . '/' . $envelope . '.php';
        $said = @file_get_contents($path);

        if ($said === false) {
            return '';
        }

        preg_match('/@phpstan-type Data (.*)/', $said, $shape);

        return trim($shape[1] ?? '');
    }

    /**
     * A value read against a type that may be several.
     *
     * The best-matching alternative wins rather than the first, because a union
     * is how the contract writes a tagged shape — and a payload judged against
     * the wrong arm of one would be reported as missing every field of a shape
     * it was never claiming to be.
     *
     * @param  array<array-key, mixed>  $value
     * @return list<string>
     */
    private static function against(string $type, array $value, string $path): array
    {
        $best = null;

        foreach (self::alternatives($type) as $alternative) {
            $said = self::one($alternative, $value, $path);

            if ($said === []) {
                return [];
            }

            if ($best === null || count($said) < count($best)) {
                $best = $said;
            }
        }

        return $best ?? [sprintf('`%s` holds an array and the contract describes none there.', $path)];
    }

    /**
     * A value read against one type.
     *
     * @param  array<array-key, mixed>  $value
     * @return list<string>
     */
    private static function one(string $type, array $value, string $path): array
    {
        if (str_starts_with($type, 'array{')) {
            return self::shaped($type, $value, $path);
        }

        if (str_starts_with($type, 'list<')) {
            return self::listed(self::inside($type, 'list<'), $value, $path);
        }

        if (str_starts_with($type, 'array<')) {
            return self::mapped(self::inside($type, 'array<'), $value, $path);
        }

        return [sprintf('`%s` holds an array where the contract says `%s`.', $path, $type)];
    }

    /**
     * A value read against a named shape, in both directions.
     *
     * @param  array<array-key, mixed>  $value
     * @return list<string>
     */
    private static function shaped(string $type, array $value, string $path): array
    {
        $fields = self::fieldsOf($type);
        $said = [];

        foreach ($value as $name => $held) {
            if (! array_key_exists((string) $name, $fields)) {
                $said[] = sprintf('`%s.%s` is not a field the contract has there.', $path, $name);

                continue;
            }

            $against = $fields[(string) $name][1];

            $said = [...$said, ...(is_array($held)
                ? self::against($against, $held, $path . '.' . $name)
                : self::said($against, $held, $path . '.' . $name))];
        }

        foreach ($fields as $name => [$optional]) {
            if (! $optional && ! array_key_exists($name, $value)) {
                $said[] = sprintf('`%s.%s` is a field every stack sends and this payload leaves out.', $path, $name);
            }
        }

        return $said;
    }

    /**
     * A value that is not an array, read against a type that may be a closed set.
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
    private static function said(string $type, mixed $value, string $path): array
    {
        $allowed = [];

        foreach (self::alternatives($type) as $alternative) {
            if (preg_match("/^'(.*)'$/s", $alternative, $said) !== 1) {
                return [];
            }

            $allowed[] = $said[1];
        }

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
     * Every entry of a list, read against the type the list holds.
     *
     * @param  array<array-key, mixed>  $value
     * @return list<string>
     */
    private static function listed(string $holds, array $value, string $path): array
    {
        $said = [];

        foreach ($value as $at => $entry) {
            if (is_array($entry)) {
                $said = [...$said, ...self::against($holds, $entry, sprintf('%s[%s]', $path, $at))];
            }
        }

        return $said;
    }

    /**
     * Every value of a map, read against the type the map holds.
     *
     * The key's own type is not checked: the contract writes these as
     * `array<string, …>` throughout, and a payload decoded from JSON has string
     * keys by construction.
     *
     * @param  array<array-key, mixed>  $value
     * @return list<string>
     */
    private static function mapped(string $holds, array $value, string $path): array
    {
        $parts = self::split($holds, ',');
        $under = $parts[1] ?? '';
        $said = [];

        foreach ($value as $name => $entry) {
            if (is_array($entry)) {
                $said = [...$said, ...self::against($under, $entry, sprintf('%s.%s', $path, $name))];
            }
        }

        return $said;
    }

    /**
     * The fields one `array{…}` declares, and whether each is required.
     *
     * @return array<string, array{0: bool, 1: string}>
     */
    private static function fieldsOf(string $type): array
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
    private static function inside(string $type, string $opens): string
    {
        return trim(substr($type, strlen($opens), -1));
    }

    /**
     * One type's alternatives, where it is a union.
     *
     * @return list<string>
     */
    private static function alternatives(string $type): array
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
    private static function split(string $type, string $on): array
    {
        $parts = [];
        $held = '';
        $depth = 0;

        foreach (self::characters($type) as $character) {
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

    /**
     * One string as its characters.
     *
     * @return list<string>
     */
    private static function characters(string $said): array
    {
        $characters = [];

        for ($at = 0; $at < strlen($said); ++$at) {
            $characters[] = $said[$at];
        }

        return $characters;
    }
}
