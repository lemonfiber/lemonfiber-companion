<?php

declare(strict_types=1);

use Tests\Support\Template;

// Two things a screen may not do to the person reading it.
//
// Nothing may flash or blink, and a layout must adapt to a small viewport
// without scrolling sideways. Both are true of this app today, and neither was
// true because anybody decided it: `EdgeVocabularyTest` refuses a class the
// platform's parser does not know, and the parser does not know `animate-pulse`
// or `w-[400px]`. So the accessibility requirement is currently enforced by a
// dependency's feature set.
//
// That is the arrangement this file exists to end. The day NativePHP's parser
// learns a word for animation — a reasonable thing for it to learn — the
// vocabulary rule starts accepting it, the ban on repeating movement stops
// being held, and no rule anywhere goes red. A requirement kept by what a
// dependency happens not to
// support is one upgrade from being gone, and the upgrade will look like an
// improvement.
//
// So the words are refused here by name, whatever the parser later thinks of
// them.
//
// **This is not a style rule.** Movement that repeats is the one visual
// effect with a medical floor under it: three flashes a second is the threshold
// a seizure is induced at, and a status that pulses while a service is
// crash-looping is exactly the design somebody reaches for. The requirement is
// absolute for that reason and this rule is too — there is no rate that makes
// it safe and therefore no allowance to write.
//
// **The width half is narrowed to what cannot be right**, which is what keeps
// it honest. A fixed width in device pixels and a viewport-width class cannot
// adapt to a screen narrower than themselves, and a sideways scroll container
// is the thing the requirement names. A small numbered width — a 48dp square
// for a touch target — is none of those and is left alone. This does not prove
// a layout adapts; it refuses the three ways of writing one that cannot.

/**
 * A class list per template, flattened to the words in it.
 *
 * Named for this file: the root suites share one namespace (G10).
 *
 * @return array<string, list<string>>
 */
function everyClassWrittenDown(): array
{
    $found = [];

    foreach (Template::all() as $template) {
        $words = [];

        foreach ($template->classStrings() as $list) {
            // `preg_split` answers false only for a pattern that cannot compile,
            // and this one is a literal — but the analyser cannot know that, and
            // a cast would be a claim rather than a check.
            $split = preg_split('/\s+/', trim($list), flags: PREG_SPLIT_NO_EMPTY);

            $words = [...$words, ...(is_array($split) ? $split : [])];
        }

        if ($words !== []) {
            $found[$template->path] = $words;
        }
    }

    return $found;
}

it('G3-R6 — nothing on a screen flashes, blinks or pulses', function (): void {
    $moving = [];

    foreach (everyClassWrittenDown() as $path => $words) {
        foreach ($words as $word) {
            if (preg_match('/^(?:animate-|transition|duration-|ease-|motion-)|(?:blink|pulse|flash)/i', $word) === 1) {
                $moving[] = sprintf('%s writes `%s`', $path, $word);
            }
        }
    }

    sort($moving);

    expect($moving)->toBe([], sprintf(
        "These make something on a screen move on its own:\n  %s\n\n"
        . 'Repeating movement is the one visual effect with a medical floor under it, and a status '
        . 'that pulses while a service is crash-looping is the design somebody reaches for. There '
        . 'is no rate that makes it safe, so there is no allowance here to write. Say the state in '
        . "words (G3-R6).",
        implode("\n  ", $moving),
    ));
});

it('G3-R8 — no screen is written wider than the phone showing it', function (): void {
    $wide = [];

    foreach (everyClassWrittenDown() as $path => $words) {
        foreach ($words as $word) {
            // An arbitrary value, a viewport width, or a sideways scroll: the
            // three ways of writing a layout that cannot adapt. A numbered
            // width is not one of them and is not looked at.
            if (preg_match('/^(?:min-|max-)?w-\[|^w-screen$|^overflow-x-/', $word) === 1) {
                $wide[] = sprintf('%s writes `%s`', $path, $word);
            }
        }
    }

    sort($wide);

    expect($wide)->toBe([], sprintf(
        "These cannot fit a screen narrower than themselves:\n  %s\n\n"
        . 'A width in device pixels and a viewport-width class are fixed against a device that is '
        . 'not, and a sideways scroll container is the thing the requirement names. The phone this '
        . 'runs on is the narrow viewport, and an operator reading a log line has no second hand '
        . "to scroll with (G3-R8).",
        implode("\n  ", $wide),
    ));
});
