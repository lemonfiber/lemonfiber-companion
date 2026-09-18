<?php

declare(strict_types=1);

use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Undoing;
use Tests\Support\Catalogue;

// L1/L2 — every case a screen branches on has a word, in every language.
//
// These enums deliberately carry no label: what a screen shows for a case is
// text a person reads, so it comes from the translator against a key, and a
// name written in the enum would be English on a Dutch phone. That
// decision leaves a gap nothing else can see — an enum and a catalogue that
// have drifted apart still compile, still pass every architecture rule, and
// render the key where a word belongs.
//
// Written over `cases()` rather than over a list of keys, so a case added to
// any of them fails here until somebody writes what it says, in both languages.
//
// `Overall` made it three, which is where the earlier note here said to stop
// writing them out and drive it from a table instead.

/**
 * What a case is called in the catalogue.
 *
 * @param list<BackedEnum> $cases
 *
 * @return list<string>
 */
function keyedBy(array $cases): array
{
    return array_map(static fn(BackedEnum $case): string => (string) $case->value, $cases);
}

/**
 * The groups this checks, and the cases each one must have a word for.
 *
 * Named once because both rules below ask the same question of the same enums,
 * and a second list is the one that stops being updated.
 *
 * `Overall` earns its row from the opening: the app opens on the verdict, and
 * `Unknown` has to arrive as a sentence of its own. Left out of the catalogue
 * it renders as `health.overall.unknown`, which a person reads as this app
 * having broken rather than as the stack having declined to say.
 *
 * `Undoing` earns its row from a repair's clauses, and is the reason that clause is an enum
 * rather than the wire's boolean: "whether it can be undone" has to reach the
 * operator as a sentence before they agree to something permanent, and a
 * boolean has no sentence to reach them with.
 *
 * Each `cases()` is written out rather than reached through a list of class
 * names, because a class name held in a variable is a set the analyser cannot
 * see, and the shorter table would buy its brevity by going dark (P2, E1).
 *
 * @return array<string, list<string>>
 */
function groups(): array
{
    return [
        'category' => keyedBy(Category::cases()),
        'conclusion' => keyedBy(Conclusion::cases()),
        'overall' => keyedBy(Overall::cases()),
        'undoing' => keyedBy(Undoing::cases()),
    ];
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

it('L1/L2 — every case a screen branches on has a word in every language', function (): void {
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
