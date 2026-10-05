<?php

declare(strict_types=1);

use Tests\Support\Template;

// F19 — every line of text takes its colour from a theme role.
//
// A text element with no colour of its own is drawn in the platform's default,
// which is black whatever the reader's setting. On a dark screen that is a
// sentence in the accessibility tree and nowhere on the glass: the remedy under
// an obstacle was exactly that on an iPhone in dark mode, and every template
// rule passed, because each reads what a template says rather than what colour
// it comes out.
//
// The design module's elements carry a role each, so text drawn through them
// is right in both settings. This refuses the one way round them.

/**
 * Every text element written without a theme colour, as `path:line`.
 *
 * Named for this file: the root suites share one namespace (G10).
 *
 * @return list<string>
 */
function textWithoutAThemeColour(): array
{
    $uncoloured = [];

    foreach (Template::all() as $template) {
        foreach ($template->elements() as $element) {
            if ($element['tag'] === 'text' && ! str_contains($element['attributes'], 'text-theme-')) {
                $uncoloured[] = sprintf('%s:%d', $template->path, $element['line']);
            }
        }
    }

    sort($uncoloured);

    return $uncoloured;
}

it('finds text elements to judge', function (): void {
    $judged = array_filter(
        Template::all(),
        static fn(Template $template): bool => array_filter(
            $template->elements(),
            static fn(array $element): bool => $element['tag'] === 'text',
        ) !== [],
    );

    expect($judged)->not->toBe([]);
});

it('every text element takes its colour from a theme role', function (): void {
    expect(textWithoutAThemeColour())->toBe([], sprintf(
        "These draw text in the platform's default colour, which is black on a dark screen:\n  %s\n\n"
        . 'Draw it through a design element (`x-design::body`, `x-design::note` and the rest), '
        . 'each of which carries a theme role with a value in each theme (DES-R33, N4-R14).',
        implode("\n  ", textWithoutAThemeColour()),
    ));
});
