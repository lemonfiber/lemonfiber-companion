<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function count;
use function is_numeric;
use function mb_ucfirst;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function trim;

/**
 * A payload of the shape one envelope declares, built from the declaration.
 *
 * `N1-R59` refuses material written by hand. The reason is one this repository
 * has already paid for: a fixture written by the author of its reader proves
 * that both are wrong in the same way, and a stand-in is nothing but fixtures —
 * so a hand-written one would let every screen render beautifully against a
 * shape no stack sends.
 *
 * So nothing is written down. The shape is read off the installed SDK's own
 * `@phpstan-type Data` line at the moment it is asked for, and a conforming
 * value is built from it. There is no file to regenerate and no diff gate to
 * keep, because there is nothing that could drift: a contract change is picked
 * up by the next call.
 *
 * **What it produces is deliberately obvious.** A string comes out as the field
 * that asked for it — `name` becomes `Name`, `describes` becomes `Describes` —
 * rather than as something that reads like a real household. Anybody looking at
 * a screen should be able to tell in a second that they are looking at a
 * stand-in, and plausible data is exactly what would stop them.
 */
final readonly class WhatAStackWouldSay
{
    /** How many entries a list is given, which is enough to see a list is a list. */
    private const int A_FEW = 2;

    /** What an integer is worth, small enough to read and not zero. */
    private const int A_NUMBER = 3;

    /**
     * The payload one envelope declares, as data ready to be encoded.
     *
     * `mixed` rather than a union of the four shapes this can answer with,
     * because which one it is, is the contract's decision and not this class's:
     * a caller asking for `StatusEnvelope` gets a map and one asking for
     * `PullEnvelope` gets a string, and the only thing that knows which is the
     * `@phpstan-type` line being read at the moment of the call. A narrower
     * declaration here would be a promise made about a file that is not this
     * one.
     */
    public static function inside(string $envelope): mixed
    {
        return self::forThe(WhatTheContractDeclares::shapeOf($envelope), 'it');
    }

    /**
     * A value of one declared type.
     *
     * `$called` is the field the type was declared under, and it is carried
     * purely so a string can say which field it came from. A type does not know
     * its own name and a payload full of the word `string` is one nobody can
     * read.
     */
    public static function forThe(string $type, string $called, int $at = 0): mixed
    {
        $type = trim($type);

        if ($type === '') {
            return null;
        }

        // A union is settled before anything else, and the order matters. Two
        // `array{…}` arms joined by `|` both start with `array{`, so a shape
        // check first reads the whole union as one shape and hands every arm's
        // fields to `fieldsOf` together — which merges them. `disturbs` is the
        // case: `array{bound: 'bounded', seconds: int}|array{bound:
        // 'open-ended', until: 'downloads'}` came out carrying `seconds` and
        // `until` at once, which is a payload no stack sends and every reader
        // would be right to refuse.
        return count(WhatTheContractDeclares::alternatives($type)) > 1
            ? self::oneOf($type, $called, $at)
            : self::whicheverShapeItIs($type, $called, $at);
    }

    /**
     * A value of one declared type that is known not to be a union.
     *
     * Split from {@see forThe()} because the two answer different questions and
     * only one of them is order-dependent: above, *is this a choice between
     * shapes*, which has to be asked first; here, *which shape*, where the
     * prefixes are mutually exclusive and the order is only convention.
     */
    private static function whicheverShapeItIs(string $type, string $called, int $at = 0): mixed
    {
        if (str_starts_with($type, 'array{')) {
            return self::theFields($type, $called, $at);
        }

        if (str_starts_with($type, 'list<')) {
            return self::aFewOf(WhatTheContractDeclares::inside($type, 'list<'), $called);
        }

        return str_starts_with($type, 'array<')
            ? self::keyedBy(WhatTheContractDeclares::inside($type, 'array<'), $called, $at)
            : self::oneOf($type, $called, $at);
    }

    /**
     * Every field of an `array{…}`, optional ones included.
     *
     * Included rather than omitted, because what this is for is looking at
     * screens: a field left out is a row that does not render, and a stand-in
     * whose job is to make every screen reachable would be hiding the parts
     * most worth looking at. `N1-R59` is satisfied either way — both are shapes
     * the contract declares — so the one that shows more is the one to build.
     *
     * @return array<string, mixed>
     */
    private static function theFields(string $type, string $called, int $at = 0): array
    {
        $said = [];

        // The first of the pair is whether the contract allows the field to be
        // absent, and this builds it either way — the docblock above says why —
        // so it is skipped rather than bound to a name nothing reads.
        foreach (WhatTheContractDeclares::fieldsOf($type) as $field => [, $declared]) {
            $said[$field] = self::forThe($declared, $field, $at);
        }

        return $said === [] ? [$called => null] : $said;
    }

    /**
     * A short list of whatever it holds.
     *
     * Two rather than one, because a screen that renders a list of one looks
     * the same as a screen that renders the only thing there is — and the
     * difference between those two is exactly what somebody is looking at a
     * list for.
     *
     * @return list<mixed>
     */
    private static function aFewOf(string $type, string $called): array
    {
        $said = [];

        for ($at = 0; $at < self::A_FEW; $at++) {
            $said[] = self::forThe($type, $called, $at);
        }

        return $said;
    }

    /**
     * A map, given one entry under a readable key.
     *
     * The key type is discarded: every `array<K, V>` in this contract is keyed
     * by a string, and a generated key that tried to honour an `int` key would
     * produce a list where the reader expects a map.
     *
     * @return array<string, mixed>
     */
    private static function keyedBy(string $type, string $called, int $at = 0): array
    {
        // The last part rather than the second, which is the same answer
        // without asking whether a key type was written: `array<string, Foo>`
        // and `array<Foo>` both declare `Foo` last. `C9` is right that the
        // `??` it replaces was an admission nobody knew what was there.
        $parts = WhatTheContractDeclares::split($type, ',');
        $holds = $parts === [] ? 'string' : $parts[count($parts) - 1];

        return [$called => self::forThe($holds, $called, $at)];
    }

    /**
     * A leaf, or the first arm of a union that is not null.
     *
     * The first arm rather than a chosen one, because the contract writes the
     * arms in an order somebody decided and a stand-in has no better opinion
     * than theirs. `null` is stepped over wherever anything else is offered:
     * a field that can be absent is most worth seeing present.
     */
    private static function oneOf(string $type, string $called, int $at = 0): mixed
    {
        $arms = WhatTheContractDeclares::alternatives($type);

        foreach ($arms as $arm) {
            $arm = trim($arm);

            if ($arm === WhatALeafIsCalled::Null->value || $arm === '') {
                continue;
            }

            // Back through the front door, because an arm may be a shape, a
            // list or a leaf and only the caller of `forThe()` knows which.
            // Recursion terminates because an arm of a union is never itself a
            // union — `split` has already taken it apart.
            return $arm === $type ? self::aLeaf($arm, $called, $at) : self::forThe($arm, $called, $at);
        }

        return null;
    }

    /**
     * One scalar, by what its type says it is.
     *
     * Split where the question changes, which `H8` is right about: above, *did
     * the contract write the value down*, and a type that is its own value is
     * answered by handing it back; below, *what is this type called*, which is
     * a vocabulary rather than a value.
     */
    private static function aLeaf(string $type, string $called, int $at = 0): mixed
    {
        // A quoted alternative is a literal the contract permits, and the
        // literal itself is the only correct value — a made-up string in its
        // place is the one thing a reader is guaranteed to refuse.
        if (str_starts_with($type, "'") && str_ends_with($type, "'")) {
            return trim($type, "'");
        }

        return is_numeric($type)
            ? self::thatNumber($type)
            : self::whateverThatTypeIsCalled($type, $called, $at);
    }

    /**
     * The field's own name, and which row of a list it is on.
     *
     * The first entry carries no number, so a field that is not in a list reads
     * as it always did — and the second is the one that proves the rows are
     * being told apart.
     */
    private static function named(string $called, int $at): string
    {
        $said = mb_ucfirst(str_replace('_', ' ', $called));

        return $at > 0 ? sprintf('%s %d', $said, $at + 1) : $said;
    }

    /**
     * A literal the contract wrote as a number, handed back as one.
     *
     * A reader checking `is_int` on a field declared `0` would refuse the
     * string `'0'`, and JSON carries the difference — so the conversion is part
     * of answering correctly rather than a cast to quiet an analyser.
     */
    private static function thatNumber(string $type): float|int
    {
        return str_contains($type, '.') ? (float) $type : (int) $type;
    }

    /** A value for a type named rather than written out. */
    private static function whateverThatTypeIsCalled(string $type, string $called, int $at = 0): mixed
    {
        $named = WhatALeafIsCalled::tryFrom($type);

        if ($named instanceof WhatALeafIsCalled) {
            return $named->carries();
        }

        return match (true) {
            str_contains($type, 'int'), str_contains($type, 'float') => self::A_NUMBER,
            // Anything else is a string, and it says which field it is, so a
            // screen full of these reads as a screen full of stand-ins. It also
            // says which row of a list it is on, because a list whose entries
            // are identical cannot show what a list is for — four services all
            // called `Name` is a screen nobody can look at and see whether the
            // rows are telling them apart. The first carries no number, so a
            // field that is not in a list reads as it always did.
            default => self::named($called, $at),
        };
    }
}
