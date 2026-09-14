<?php

declare(strict_types=1);

use Tests\Support\Template;
use Tests\Support\Tree;

// F5, F6, F11 and L1 — the things a screen owes the person reading it.
//
// None of these is a crash. A control with no label is silent to a screen
// reader, a list with no empty state renders as nothing at all, and an English
// sentence written into a template is English on a Dutch device. All of them
// ship green and are found by the operator.

$templates = Template::all();

/**
 * Every component a screen is built from, and whether a person operates it.
 *
 * `true` is a control: something acted on, which therefore has to say what it
 * is. `false` is furniture — it renders, and a reader passes over it on the way
 * to the text.
 *
 * A table over every component rather than a list of the ones that matter,
 * because a list is only as complete as whoever last remembered it. `F11`
 * refuses a component this table has no word for, so a control nobody thought
 * about fails the build instead of passing quietly, which is the whole of what
 * `N4-R21` is asking for.
 *
 * Classification is by kind and a kind cannot see everything: a component
 * classified as furniture that is nevertheless given something to do is a
 * control whatever this table says about its sort. {@see isOperated()} is that
 * second reading, and the two are deliberately independent — a control with its
 * handler wired in PHP carries no attribute, and a tappable line of text is not
 * of a kind anyone would list.
 */
const WHAT_A_COMPONENT_IS = [
    // A column is a box; announcing it puts a word between the reader and
    // everything inside it. Text is read out as itself and has no separate name
    // to give.
    'column' => false,
    'text' => false,

    'button' => true,
    'outlined-text-input' => true,
];

/**
 * The attributes that hand a component something to do.
 *
 * `native:model` is here with the handlers because a field bound to a property
 * is typed into, and typing into something is operating it.
 */
const OPERATED_BY = ['@tap', '@press', '@navigate', 'native:model'];

foreach ($templates as $template) {
    it(sprintf('F5 — every control in %s announces itself', $template->path), function () use ($template): void {
        $silent = [];
        $controls = 0;

        foreach ($template->elements() as $element) {
            if (! isAControl($element['tag'], $element['attributes'])) {
                continue;
            }

            $controls++;

            if (announcesItself($element['attributes'])) {
                continue;
            }

            $silent[] = $template->describe($element['tag'], $element['line']);
        }

        // The floor is one per screen and it is not a guess. Every screen owes
        // the operator a way off it, a way off is something that navigates, and
        // something that navigates is operated — so a screen this rule finds no
        // control on is a screen where the reading, not the screen, is wrong.
        expect($controls)->toBeGreaterThan(0, sprintf(
            '%s has no control this rule can see, so it read nothing here',
            $template->path,
        ));

        expect($silent)->toBe([], sprintf(
            "These controls are silent to a screen reader:\n  %s\n\n"
            . 'The package\'s own contract asks for `a11y-label` on a control with no '
            . 'visible text — iOS reads it as accessibilityLabel and Android as '
            . 'contentDescription — and an icon-only control has nothing else for the '
            . "reader to fall back on.\nA `label` attribute counts, because that text is "
            . 'visible and is what gets read. Anything else needs `a11y-label` (F5, '
            . 'N4-R21).',
            implode("\n  ", $silent),
        ));
    });

    it(sprintf('F11 — every component in %s has been decided about', $template->path), function () use ($template): void {
        $unclassified = [];

        foreach ($template->elements() as $element) {
            if (array_key_exists($element['tag'], WHAT_A_COMPONENT_IS)) {
                continue;
            }

            $unclassified[] = $template->describe($element['tag'], $element['line']);
        }

        expect($unclassified)->toBe([], sprintf(
            "These components are on a screen and nothing has said what they are:\n  %s\n\n"
            . 'Say so in `WHAT_A_COMPONENT_IS`: `true` where a person operates it, `false` '
            . "where it is furniture.\nUntil something does, `F5` passes over it — and a "
            . 'control `F5` passes over is one that reaches somebody using a screen reader '
            . 'with nothing to say for itself, which is the failure `F5` is written against '
            . '(F11, N4-R21).',
            implode("\n  ", $unclassified),
        ));
    });

    it(sprintf('F6 — every list in %s has an empty state', $template->path), function () use ($template): void {
        $unguarded = [];

        preg_match_all('/@foreach\b/', $template->source, $found, PREG_OFFSET_CAPTURE);

        foreach ($found[0] as $match) {
            $unguarded[] = sprintf('%s:%d', $template->path, $template->lineAt($match[1]));
        }

        expect($unguarded)->toBe([], sprintf(
            "These iterate without saying what nothing looks like:\n  %s\n\n"
            . '`@foreach` over an empty collection renders nothing, and nothing is '
            . 'indistinguishable from still loading and from a stack that cannot be '
            . 'reached — which are three different screens with three different things '
            . "for the operator to do.\n`@forelse` with `@empty` makes writing the empty "
            . 'case the same edit as writing the loop, rather than a second one somebody '
            . 'has to remember (F6, N2-R1).',
            implode("\n  ", $unguarded),
        ));
    });

    it(sprintf('L1 — %s reads its text from the translator', $template->path), function () use ($template): void {
        $literals = array_values(array_filter($template->proseNodes(), readsAsProse(...)));

        expect($literals)->toBe([], sprintf(
            "These sentences are written into the template:\n  %s\n\n"
            . 'The application ships en and nl, so a sentence here is English on a Dutch '
            . 'device and the parity check cannot see it — it is in neither catalogue. '
            . "Put it in lang/<locale>/<module>.php and echo `__('module.key')`, with a "
            . 'replacement array where a value goes in (L1, L2).',
            implode("\n  ", $literals),
        ));
    });
}

/**
 * Whether a person operates this element, read both ways.
 *
 * The table answers by kind and {@see isOperated()} answers by what the element
 * was given to do. Either is enough: a button with its handler wired in PHP
 * carries no attribute of ours, and a line of text made tappable is a control
 * that no table of component kinds would ever have listed.
 */
function isAControl(string $tag, string $attributes): bool
{
    if (isOperated($attributes)) {
        return true;
    }

    // Absent is not furniture. An unclassified component is `F11`'s to report,
    // and answering `false` here would be this rule deciding the question that
    // rule exists to make somebody answer.
    return array_key_exists($tag, WHAT_A_COMPONENT_IS) && WHAT_A_COMPONENT_IS[$tag];
}

/** Whether an element has been handed something to do. */
function isOperated(string $attributes): bool
{
    return array_any(
        OPERATED_BY,
        fn(string $attribute): bool => preg_match(sprintf('/(?<![\w:@-])%s\s*=/', preg_quote($attribute, '/')), $attributes) === 1,
    );
}

/** Whether an element's attributes give a screen reader something to say. */
function announcesItself(string $attributes): bool
{
    return array_any(['a11y-label', 'a11yLabel', 'label'], fn(string $attribute): bool => preg_match(sprintf('/(?<![\w-])%s\s*=/', preg_quote($attribute, '/')), $attributes) === 1);
}

/**
 * Whether a run of text is a sentence rather than punctuation or a fragment.
 *
 * Two words and a capital, the same test the PHPStan rule uses on presenters,
 * so the two halves of L1 agree about what counts as prose.
 */
function readsAsProse(string $text): bool
{
    return preg_match('/\p{Lu}/u', $text) === 1
        && preg_match('/\p{L}{2,}\s\p{L}{2,}/u', $text) === 1;
}

it('every screen offers a way off it', function (): void {
    // The other direction of reachability, and the one a test cannot feel. A
    // screen that resolves, renders and reads perfectly is still a dead end if
    // there is nothing on it that goes anywhere — and the platform's own back
    // gesture may or may not be there, so a screen that counts on it works on
    // one handset and traps somebody on another.
    //
    // Over every screen, with no exemptions, which is the strongest form this
    // could take and turned out to be the true one. The pairing roads are not
    // under a machine — there is no machine yet — and were both dead ends until
    // somebody noticed by hand; the opening list is the floor and still goes
    // somewhere, because pairing is reached from it.
    $trapped = [];
    $asked = 0;

    foreach (Template::all() as $template) {
        $asked++;

        if (! str_contains((string) file_get_contents(Tree::at($template->path)), '@navigate=')) {
            $trapped[] = $template->path;
        }
    }

    expect($asked)->toBeGreaterThan(1, 'no template was asked, so this rule read nothing');

    expect($trapped)->toBe([], sprintf(
        "These are screens a person can get to and not get off:\n  %s\n\n"
        . 'Every screen ends with somewhere to go. A screen that resolves, renders and reads '
        . "perfectly is still a dead end when nothing on it navigates anywhere.\n",
        implode("\n  ", $trapped),
    ));
});

it('every screen under a machine offers a way back to it', function (): void {
    // The stronger half, and a narrower one. The rule above is satisfied by any
    // destination at all, so a screen that only went *deeper* — to a service's
    // logs, say — would pass it while still leaving somebody one level down
    // with nowhere up.
    //
    // Kept as its own rule rather than folded into the one above, because the
    // two ask different questions and a single rule answering both would be
    // satisfied by whichever was easier.
    $trapped = [];

    foreach (whichTemplatesSitUnderAMachine() as $path => $said) {
        if (! str_contains($said, 'goes()->health()')) {
            $trapped[] = $path;
        }
    }

    expect(whichTemplatesSitUnderAMachine())
        ->not->toBe([], 'no template was found under a machine, so this rule read nothing');

    expect($trapped)->toBe([], sprintf(
        "These are screens under a machine with no way back to it:\n  %s\n\n"
        . 'Every leaf under a machine ends with a way back to it. Going deeper is not a way '
        . "out, and the screen above is where somebody came from.\n",
        implode("\n  ", $trapped),
    ));
});

/**
 * The templates of screens that sit under one machine, by path.
 *
 * Found by what they draw rather than by a list: a screen under a machine reads
 * that machine's name off the route, so it is the ones calling `stack()`.
 *
 * Two are named out, each with its reason, because an exemption nobody can read
 * is how a rule quietly stops covering what it was written for. The **hub** is
 * what the others go back to. The **sign-in** is where somebody lands when a
 * session has ended, and offering *back to the machine* from it would be
 * offering the screen that just turned them away.
 *
 * @return array<string, string>
 */
function whichTemplatesSitUnderAMachine(): array
{
    $under = [];

    foreach (Template::all() as $template) {
        $said = (string) file_get_contents(Tree::at($template->path));

        if (! str_contains($said, '$this->stack()')) {
            continue;
        }

        if (str_contains($template->path, 'how-this-stack-is') || str_contains($template->path, 'sign-into-a-stack')) {
            continue;
        }

        $under[$template->path] = $said;
    }

    return $under;
}
