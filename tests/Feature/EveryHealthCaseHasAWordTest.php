<?php

declare(strict_types=1);

use Modules\Health\Api\Category;
use Modules\Health\Api\Conclusion;
use Tests\Support\Catalogue;

// L1/L2 — every case a screen branches on has a word, in every language.
//
// `Category` and `Conclusion` deliberately carry no label: what a screen shows
// for one is text a person reads, so it comes from the translator against a key
// and a name written in the enum would be English on a Dutch phone. That
// decision leaves a gap nothing else can see — an enum and a catalogue that
// have drifted apart still compile, still pass every architecture rule, and
// render the key where a word belongs.
//
// Written over `cases()` rather than over a list of keys, so a case added to
// either enum fails here until somebody writes what it says, in both languages.
//
// Two enums in one file because they are one requirement. A third would be the
// point to ask whether this should be driven by a table rather than written
// out; two is a pair.

/** @return list<string> */
function categoryValues(): array
{
    return array_map(static fn(Category $case): string => $case->value, Category::cases());
}

/** @return list<string> */
function conclusionValues(): array
{
    return array_map(static fn(Conclusion $case): string => $case->value, Conclusion::cases());
}

/**
 * The groups this checks, and the cases each one must have a word for.
 *
 * Named once because both rules below ask the same question of the same two
 * enums, and a second list is the one that stops being updated.
 *
 * @return array<string, list<string>>
 */
function groups(): array
{
    return ['category' => categoryValues(), 'conclusion' => conclusionValues()];
}

/**
 * Every word one group holds, in one locale, keyed the way the catalogue keys it.
 *
 * @param list<string> $values
 *
 * @return array<string, string>
 */
function wordsFor(string $locale, string $group, array $values): array
{
    $words = Catalogue::of($locale, 'health');

    $found = [];

    foreach ($values as $value) {
        $key = sprintf('health.%s.%s', $group, $value);

        if (array_key_exists($key, $words)) {
            $found[$key] = $words[$key];
        }
    }

    return $found;
}

it('L2 — every category and conclusion has a word in every language', function (): void {
    $missing = [];

    foreach (Catalogue::locales() as $locale) {
        $words = Catalogue::of($locale, 'health');

        foreach (groups() as $group => $values) {
            foreach ($values as $value) {
                $key = sprintf('health.%s.%s', $group, $value);

                if (! array_key_exists($key, $words)) {
                    $missing[] = sprintf('%s is missing %s', $locale, $key);
                }
            }
        }
    }

    sort($missing);

    expect($missing)->toBe([], sprintf(
        "These have no word a person can read:\n  %s\n\n"
        . 'The enums carry no label on purpose — a name written in PHP is English on a '
        . 'Dutch phone — so the catalogue is the only place the word exists. A case with '
        . 'no row renders as its key, which reads as this app having broken (L1, L2).',
        implode("\n  ", $missing),
    ));
});

it('L2 — no two cases in a group read the same', function (): void {
    // `Unverified` against `Passed` is the pair this exists for. A check that
    // could not run must never be readable as one that passed — that is the
    // distinction the whole subsystem turns on, and it survives the type system
    // only to be lost in a catalogue where two rows say the same thing. Two
    // categories sharing a heading is the same fault on a screen an operator
    // navigates by.
    $collisions = [];

    foreach (Catalogue::locales() as $locale) {
        foreach (groups() as $group => $values) {
            $collisions = [
                ...$collisions,
                ...Catalogue::saidTwice($locale, wordsFor($locale, $group, $values)),
            ];
        }
    }

    sort($collisions);

    expect($collisions)->toBe([], sprintf(
        "These are distinguished everywhere except where it counts:\n  %s\n\n"
        . 'The enum keeps the cases apart and the operator meets the sentence. Two of '
        . 'them sharing a word is the collapse arrived at by the back door (L2).',
        implode("\n  ", $collisions),
    ));
});
