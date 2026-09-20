<?php

declare(strict_types=1);

use Tests\Support\Tree;

// F8 — a screen shows findings in the order the capability decided.
//
// `WorstFirst` is that decision and the only one `health` makes about a report:
// what it costs first, the verdict to break the tie, equals left in the order
// the checks ran. A screen that renders `Findings` straight off the envelope
// shows the order the checks ran and nothing else — a list that looks ordered
// and is not.
//
// Nothing about that is visible. The rows are right, the words are right, and
// on a report whose worst finding happens to have run first the order is right
// as well. It is wrong on the next report, on a device, with no wrong pixel for
// anybody to notice and nothing written anywhere.
//
// The other half is that the sorter cannot report it either: a query nothing
// calls passes its own test forever, so `WorstFirstTest` stays green through
// every screen that never asks. `operator/src/README.md` recorded this gate
// against the day a findings screen existed, which is the day it became
// checkable.
//
// Read as text rather than as a call graph, because what has to be caught is a
// screen that names the query nowhere at all — there is no call to follow to
// its absence. Narrow on purpose: `Internal/Screens` is where a `Findings`
// becomes rows, and a capability composing two queries is doing the thing this
// rule exists to make screens do.
//
// Narrow on purpose is also narrow enough to reach nothing. Both filters name a
// directory and an accessor this repository chose, and either renamed leaves
// the rule reading no files and reporting a green tick — which is the sentence
// above happening to the rule rather than to a screen. So it says whether it
// found a screen at all before it says none of them is at fault.

it('F8 — nothing shows findings it did not take from WorstFirst', function (): void {
    $showing = [];
    $offenders = [];

    foreach (Tree::filesUnder(Tree::at('app-modules'), '.php') as $path) {
        if (! str_contains($path, '/src/Internal/Screens/')) {
            continue;
        }

        $said = (string) file_get_contents($path);

        // The type by name, or a `findings` member read off whatever the screen
        // was handed. The second is the shape that matters: a screen can walk a
        // whole run without ever writing the type down, which is what the first
        // one here did.
        if (preg_match('/\bFindings\b|->findings\b|\bfindings\s*\(/', $said) !== 1) {
            continue;
        }

        $showing[] = $path;

        if (str_contains($said, 'WorstFirst')) {
            continue;
        }

        $offenders[] = str_replace(sprintf('%s/', Tree::root()), '', $path);
    }

    expect($showing)->not->toBe([], 'no screen under Internal/Screens puts findings on a screen, so this rule read nothing');

    sort($offenders);

    expect($offenders)->toBe([], sprintf(
        "These put findings on a screen in whatever order they arrived in:\n  %s\n\n"
        . 'The envelope carries them in the order the checks ran, which is not an order '
        . "anybody should read them in — N2-R2 asks for worst first.\n"
        . 'An unordered list looks ordered: the rows are right, the words are right, and '
        . 'on any report whose worst finding happened to run first the order is right too. '
        . 'The report after that is wrong and says nothing about it, on somebody else\'s '
        . "phone, where the operator reads a full disk above a leaking tunnel.\n"
        . 'Take them from `Modules\\Health\\Api\\Queries\\WorstFirst`, which is where that '
        . 'decision is made and tested — and which passes its own test whether or not a '
        . 'screen ever calls it (F8, N2-R2).',
        implode("\n  ", $offenders),
    ));
});
