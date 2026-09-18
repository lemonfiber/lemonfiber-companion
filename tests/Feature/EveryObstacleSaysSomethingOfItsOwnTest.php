<?php

declare(strict_types=1);

use Modules\Kernel\Api\Obstacle;
use Tests\Support\Catalogue;

// Three different things, each with its own remedy.
//
// `Obstacle` keeps them apart in the type system, which is where the analyser
// can see it. This is the half the analyser cannot: three cases that all render
// the same sentence satisfy every rule in the repository and defeat the
// requirement entirely, because what the operator meets is the sentence.
//
// Written over `Obstacle::cases()` rather than over a list of keys, so a fourth
// case fails here until somebody writes what it says — in both languages.
//
// It sits outside the module because it reads `lang/`, which a capability may
// not do, and because the pairing it checks is between a module and the
// application's text rather than anything inside either.
//
// **`G4-R1` is the other requirement this keeps.** Every user-facing error must
// state what happened, what it means, and what to do. The sentence is the first
// two and the `_action` key is the third, and the second test is what makes the
// middle one true — three obstacles that all said the same thing would each
// state *something*, and none would tell an operator which of the three they
// had met. Named here because the pair of tests is where the requirement
// actually lives; nothing else in this repository reads an error for what it
// says.

it('N1-R10 — every obstacle has a sentence and a remedy, in every language', function (): void {
    $missing = [];

    foreach (Catalogue::locales() as $locale) {
        $catalogue = Catalogue::of($locale, 'connection');

        foreach (Obstacle::cases() as $obstacle) {
            foreach (['connection.%s', 'connection.%s_action'] as $shape) {
                $key = sprintf($shape, $obstacle->value);

                if (! array_key_exists($key, $catalogue)) {
                    $missing[] = sprintf('%s is missing %s', $locale, $key);
                }
            }
        }
    }

    sort($missing);

    expect($missing)->toBe([], sprintf(
        "These obstacles have nothing to say:\n  %s\n\n"
        . 'An obstacle with no sentence renders as its key, and an obstacle with no '
        . 'remedy renders as a screen that reports a failure and offers nothing — which '
        . 'is the state N1-R10 exists to prevent. The case is the easy half; the text is '
        . 'what the operator meets.',
        implode("\n  ", $missing),
    ));
});

it('N1-R10 — no two obstacles say the same thing', function (): void {
    $collisions = [];

    foreach (Catalogue::locales() as $locale) {
        $catalogue = Catalogue::of($locale, 'connection');

        foreach (['connection.%s', 'connection.%s_action'] as $shape) {
            $said = [];

            foreach (Obstacle::cases() as $obstacle) {
                $key = sprintf($shape, $obstacle->value);
                $said[$key] = $catalogue[$key] ?? '';
            }

            $collisions = [...$collisions, ...Catalogue::saidTwice($locale, $said)];
        }
    }

    sort($collisions);

    expect($collisions)->toBe([], sprintf(
        "These obstacles are distinguished everywhere except where it counts:\n  %s\n\n"
        . 'A device with no network, a stack that did not answer and a refused credential '
        . 'are three different things with three different remedies. Giving two of them '
        . 'the same sentence is the collapse N1-R10 names, arrived at by the back door — '
        . 'the types stay apart and the operator is still told to check a machine that is '
        . 'fine.',
        implode("\n  ", $collisions),
    ));
});
