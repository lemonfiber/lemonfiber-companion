<?php

declare(strict_types=1);

use Modules\Connection\Api\Obstacle;
use Tests\Support\Tree;

// N1-R10 — three different things, each with its own remedy.
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

/**
 * One locale's connection catalogue.
 *
 * @return array<string, string>
 */
function connectionCatalogue(string $locale): array
{
    /** @var mixed $group */
    $group = require Tree::at(sprintf('lang/%s/connection.php', $locale));

    if (! is_array($group)) {
        return [];
    }

    $found = [];

    foreach ($group as $key => $value) {
        if (is_string($key) && is_string($value)) {
            $found[$key] = $value;
        }
    }

    return $found;
}

/** @return list<string> */
function locales(): array
{
    $directories = glob(Tree::at('lang/*'), GLOB_ONLYDIR);

    return array_map(basename(...), $directories === false ? [] : $directories);
}

it('N1-R10 — every obstacle has a sentence and a remedy, in every language', function (): void {
    $missing = [];

    foreach (locales() as $locale) {
        $catalogue = connectionCatalogue($locale);

        foreach (Obstacle::cases() as $obstacle) {
            foreach ([$obstacle->value, sprintf('%s_action', $obstacle->value)] as $key) {
                if (! array_key_exists($key, $catalogue)) {
                    $missing[] = sprintf('%s is missing connection.%s', $locale, $key);
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

    foreach (locales() as $locale) {
        $catalogue = connectionCatalogue($locale);

        foreach (['%s', '%s_action'] as $shape) {
            $said = [];

            foreach (Obstacle::cases() as $obstacle) {
                $key = sprintf($shape, $obstacle->value);
                $sentence = $catalogue[$key] ?? '';

                $seen = array_search($sentence, $said, strict: true);

                if ($seen !== false) {
                    $collisions[] = sprintf('%s: %s reads the same as %s', $locale, $key, $seen);
                }

                $said[$key] = $sentence;
            }
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
