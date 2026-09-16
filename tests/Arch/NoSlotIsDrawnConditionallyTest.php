<?php

declare(strict_types=1);

use Tests\Support\Template;

// F13 — a component draws its slot on every branch it has.
//
// Blade renders a slot *before* the component's own template runs, and the
// native renderer collects elements as they render. So a slot the component
// then declines to echo is still in the tree the device draws: the screen shows
// its content and the component's other arm at once, and the `@if` that was
// meant to choose between them chose nothing.
//
// It cost a screen here. `WhatStoppedTheReading` was given the screen's content
// as a slot so that one value could decide between them, and on a stack that
// could not be reached the frame drew the whole reading followed by the
// obstacle. Every template rule passed, because every template rule reads Blade
// as text and the text is correct.
//
// What found it was `WhatTheDeviceWouldDraw`, which renders the tree — and it
// found it on one screen, because that is the one screen whose test asserts the
// controls a frame offers exhaustively. Six more were drawing both arms with
// nothing to say so.
//
// The cure is that the branch belongs to the screen: `@if` on the reading,
// `@else` to the component. This refuses the shape that caused it.

/**
 * Where a conditional opens and closes, in the order Blade reads them.
 *
 * `@else` and `@elseif` are deliberately absent: they neither open nor close,
 * and content under them is inside the conditional exactly as content above
 * them is.
 *
 * `@forelse`'s `@empty` is not here either, for a reason worth stating — it
 * takes no parentheses and closes with `@endforelse`, so counting it would
 * leave every loop permanently open and report the rest of the file as
 * conditional.
 */
const OPENS_A_BRANCH = ['@if', '@unless', '@isset'];

/** @see OPENS_A_BRANCH */
const CLOSES_A_BRANCH = ['@endif', '@endunless', '@endisset'];

/**
 * How many conditionals are open at each point a template echoes its slot.
 *
 * Read by walking the directives rather than by matching a slot inside a
 * balanced block: a regex that tried to span from `@if` to `@endif` would have
 * to choose between the nearest `@endif` and the matching one, and both answers
 * are wrong on a nested template.
 *
 * Blade comments go first. One of them explains this very rule and names the
 * directives it is about, and a rule its own documentation can trip is a rule
 * whose next reader deletes the paragraph instead of the bug.
 *
 * @return list<int> one depth per echo of `$slot`, in source order
 */
function howDeepEachSlotEchoIs(string $source): array
{
    $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $source);

    preg_match_all(
        '/@\w+|\{\{\s*\$slot\s*\}\}|\{!!\s*\$slot\s*!!\}/',
        $source,
        $found,
        PREG_OFFSET_CAPTURE,
    );

    $depth = 0;
    $depths = [];

    foreach ($found[0] as [$token]) {
        if (in_array($token, OPENS_A_BRANCH, strict: true)) {
            $depth++;

            continue;
        }

        if (in_array($token, CLOSES_A_BRANCH, strict: true)) {
            $depth = max(0, $depth - 1);

            continue;
        }

        if (! str_starts_with($token, '@')) {
            $depths[] = $depth;
        }
    }

    return $depths;
}

it('finds templates that draw a slot', function (): void {
    // The floor `Q-R66` asks for, and a real one here: the rule's whole subject
    // is the echo, so a walk that found none would pass in silence — including
    // in the state where somebody had replaced every `{{ $slot }}` with
    // something this cannot read.
    $drawing = array_filter(
        Template::all(),
        static fn(Template $template): bool => howDeepEachSlotEchoIs($template->source) !== [],
    );

    expect($drawing)->not->toBe([]);
});

it('F13 — a component draws its slot on every branch it has', function (): void {
    $behindABranch = [];

    foreach (Template::all() as $template) {
        foreach (howDeepEachSlotEchoIs($template->source) as $depth) {
            if ($depth > 0) {
                $behindABranch[] = $template->path;
            }
        }
    }

    sort($behindABranch);

    expect(array_values(array_unique($behindABranch)))->toBe([], sprintf(
        "These draw their slot on some branches and not others:\n  %s\n\n"
        . 'Blade renders a slot before the component runs, and the native renderer keeps '
        . "what it rendered — so the branch that does not echo the slot draws it anyway.\n"
        . 'Put the branch in the screen instead: `@if` on what the screen has, `@else` to '
        . 'the component, and the component takes no slot. A component that must vary its '
        . "own body takes the parts as attributes rather than as a slot it might drop.\n"
        . 'Nothing else will report this. A template rule reads Blade as text, and the text '
        . 'says what it means.',
        implode("\n  ", array_unique($behindABranch)),
    ));
});
