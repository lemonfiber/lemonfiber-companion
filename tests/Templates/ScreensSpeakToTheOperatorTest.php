<?php

declare(strict_types=1);

use Tests\Support\Template;

// F5, F6 and L1 — the three things a screen owes the person reading it.
//
// None of these is a crash. A control with no label is silent to a screen
// reader, a list with no empty state renders as nothing at all, and an English
// sentence written into a template is English on a Dutch device. All three ship
// green and are found by the operator.

$templates = Template::all();

/** Elements a person acts on, and which therefore have to announce themselves. */
const INTERACTIVE = ['pressable', 'button', 'fab', 'bottom-nav-item', 'top-bar-action', 'gesture-area'];

foreach ($templates as $template) {
    it(sprintf('F5 — every control in %s announces itself', $template->path), function () use ($template): void {
        $silent = [];

        foreach ($template->elements() as $element) {
            if (! in_array($element['tag'], INTERACTIVE, strict: true)) {
                continue;
            }

            if (announcesItself($element['attributes'])) {
                continue;
            }

            $silent[] = $template->describe($element['tag'], $element['line']);
        }

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
