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

/**
 * The keys in a group that read the same as an earlier one.
 *
 * @param array<string, string> $words
 *
 * @return list<string>
 */
function readingTheSame(string $locale, array $words): array
{
    $collisions = [];
    $said = [];

    foreach ($words as $key => $word) {
        $seen = array_search($word, $said, strict: true);

        if ($seen !== false) {
            $collisions[] = sprintf('%s: %s reads the same as %s', $locale, $key, $seen);
        }

        $said[$key] = $word;
    }

    return $collisions;
}

it('L2 — every category and conclusion has a word in every language', function (): void {
    $missing = [];

    foreach (Catalogue::locales() as $locale) {
        $words = Catalogue::of($locale, 'health');

        foreach (['category' => categoryValues(), 'conclusion' => conclusionValues()] as $group => $values) {
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

it('L2 — no two conclusions read the same', function (): void {
    // `Unverified` against `Passed` is the pair this exists for. A check that
    // could not run must never be readable as one that passed — that is the
    // distinction the whole subsystem turns on, and it survives the type system
    // only to be lost in a catalogue where two rows say the same thing.
    $collisions = [];

    foreach (Catalogue::locales() as $locale) {
        $collisions = [
            ...$collisions,
            ...readingTheSame($locale, wordsFor($locale, 'conclusion', conclusionValues())),
        ];
    }

    sort($collisions);

    expect($collisions)->toBe([], sprintf(
        "These conclusions are distinguished everywhere except where it counts:\n  %s\n\n"
        . 'The enum keeps them apart and the operator meets the sentence. Two cases '
        . 'sharing a word is the collapse arrived at by the back door (L2).',
        implode("\n  ", $collisions),
    ));
});

it('L2 — no two categories read the same', function (): void {
    $collisions = [];

    foreach (Catalogue::locales() as $locale) {
        $collisions = [
            ...$collisions,
            ...readingTheSame($locale, wordsFor($locale, 'category', categoryValues())),
        ];
    }

    sort($collisions);

    expect($collisions)->toBe([], sprintf(
        "These categories are distinguished everywhere except where it counts:\n  %s\n\n"
        . 'A report narrowed to one family, shown under a heading that names two, is a '
        . 'screen an operator cannot navigate (L2).',
        implode("\n  ", $collisions),
    ));
});
