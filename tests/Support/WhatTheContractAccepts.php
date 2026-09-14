<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_key_exists;
use function count;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function str_starts_with;

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
 * first under `changelog` and gives the top-level one a different meaning
 * entirely. The fixture put them where the reader looked, three rules passed,
 * and the screen would have refused every stack with an update waiting.
 *
 * So the fixture is checked against the contract rather than against the
 * reader. Three directions, because each catches a mistake the others cannot:
 * a key the contract has not got at that path, a key it requires that the
 * payload leaves out, and a word outside a closed set it declares.
 *
 * The third is what names a defect rather than a symptom. A reader looking in
 * the wrong place often finds a field that *exists* there under another
 * meaning, so nothing is unknown and nothing is missing — only the word is
 * wrong.
 */
final readonly class WhatTheContractAccepts
{
    /**
     * Everything about a payload the contract would not recognise.
     *
     * The body as it goes on the wire rather than its `data` alone, so a suite
     * hands over exactly what it hands the client.
     *
     * @param  array<array-key, mixed>  $body
     * @return list<string>
     */
    public static function complaintsAbout(string $envelope, array $body): array
    {
        $shape = WhatTheContractDeclares::of($envelope);

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

        foreach (WhatTheContractDeclares::alternatives($type) as $alternative) {
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
            return self::listed(WhatTheContractDeclares::inside($type, 'list<'), $value, $path);
        }

        if (str_starts_with($type, 'array<')) {
            return self::mapped(WhatTheContractDeclares::inside($type, 'array<'), $value, $path);
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
        $fields = WhatTheContractDeclares::fieldsOf($type);
        $said = [];

        foreach ($value as $name => $held) {
            $at = sprintf('%s.%s', $path, $name);

            if (! array_key_exists((string) $name, $fields)) {
                $said[] = sprintf('`%s` is not a field the contract has there.', $at);

                continue;
            }

            $against = $fields[(string) $name][1];

            $said = [...$said, ...(is_array($held)
                ? self::against($against, $held, $at)
                : self::said($against, $held, $at))];
        }

        return [...$said, ...self::shortOf($fields, $value, $path)];
    }

    /**
     * The fields a stack always sends that this payload does not.
     *
     * A payload short of one is a sample of something no stack sends, and a
     * reader tested only against it has been tested against nothing.
     *
     * @param  array<string, array{0: bool, 1: string}>  $fields
     * @param  array<array-key, mixed>  $value
     * @return list<string>
     */
    private static function shortOf(array $fields, array $value, string $path): array
    {
        $said = [];

        foreach ($fields as $name => [$optional]) {
            if (! $optional && ! array_key_exists($name, $value)) {
                $said[] = sprintf(
                    '`%s.%s` is a field every stack sends and this payload leaves out.',
                    $path,
                    $name,
                );
            }
        }

        return $said;
    }

    /**
     * A value that is not an array, read against a type that may be a closed set.
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

        foreach (WhatTheContractDeclares::alternatives($type) as $alternative) {
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
        $parts = WhatTheContractDeclares::split($holds, ',');
        $under = $parts[1] ?? '';
        $said = [];

        foreach ($value as $name => $entry) {
            if (is_array($entry)) {
                $said = [...$said, ...self::against($under, $entry, sprintf('%s.%s', $path, $name))];
            }
        }

        return $said;
    }
}
