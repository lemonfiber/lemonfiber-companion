<?php

declare(strict_types=1);

use Tests\Support\Template;

// A screen's body is one scroll view, and nothing inside it opens another.
//
// `<x-operator::content>` is a scroll view around a padded column. A second one
// drawn inside it is a scroll view inside a scroll view: two regions competing
// for the same gesture, and a padded column indented inside a padded column.
// The device draws it without complaint, so the only place to catch it is the
// markup.
//
// What opens one is read off the components rather than listed here: content
// itself, and every component whose own template draws content. A component
// that starts wrapping itself in content is covered on the day it does.
//
// Read as tokens rather than rendered: a nested scroll view is a fact about
// where one tag is written relative to another, and a template that would
// draw one on some arm is wrong on every arm. Comments are blanked first,
// because the comments here name these components while explaining them.

/** The component tag that opens a screen's scrolling column. */
const THE_CONTENT = 'x-operator::content';

/** A template's source with every Blade comment blanked, so offsets still fall on the same lines. */
function theMarkupWithoutComments(string $source): string
{
    return (string) preg_replace_callback(
        '/\{\{--.*?--\}\}/s',
        static fn(array $comment): string => (string) preg_replace('/[^\n]/', ' ', $comment[0]),
        $source,
    );
}

/**
 * Every tag that opens a scroll view: content, and each component whose own
 * template draws content.
 *
 * @return list<string>
 */
function everyTagThatScrolls(): array
{
    $scrolls = [THE_CONTENT];

    foreach (Template::all() as $template) {
        if (preg_match('#^app-modules/([^/]+)/resources/views/components/(.+)\.blade\.php$#', $template->path, $named) !== 1) {
            continue;
        }

        if (str_contains(theMarkupWithoutComments($template->source), sprintf('<%s', THE_CONTENT))) {
            $scrolls[] = sprintf('x-%s::%s', $named[1], str_replace('/', '.', $named[2]));
        }
    }

    return $scrolls;
}

/** How far a tag moves the count of content still open. */
function theDepthATagAdds(string $name, bool $closes): int
{
    return match (true) {
        $name !== THE_CONTENT => 0,
        $closes => -1,
        default => 1,
    };
}

/**
 * Where a template opens a scroll view while content is still open.
 *
 * @param  list<string>  $scrolls
 * @return list<string>
 */
function everyScrollViewInsideAScrollView(Template $template, array $scrolls): array
{
    preg_match_all(
        '/<(\/?)(x-[a-z0-9.:-]+)/i',
        theMarkupWithoutComments($template->source),
        $found,
        PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
    );

    $open = 0;
    $nested = [];

    foreach ($found as $tag) {
        $name = $tag[2][0];
        $closes = $tag[1][0] === '/';

        if (! $closes && $open > 0 && in_array($name, $scrolls, strict: true)) {
            $nested[] = $template->describe($name, $template->lineAt($tag[0][1]));
        }

        $open += theDepthATagAdds($name, closes: $closes);
    }

    return $nested;
}

it('finds the tags that open a scroll view', function (): void {
    // The floor: a reading that found only content itself would pass the rule
    // below while missing every component that wraps itself in it.
    expect(everyTagThatScrolls())->toContain(THE_CONTENT, 'x-operator::what-stopped-the-reading');
});

it('opens no scroll view inside a screen\'s content', function (): void {
    $scrolls = everyTagThatScrolls();
    $read = 0;
    $nested = [];

    foreach (Template::all() as $template) {
        $read += str_contains($template->source, sprintf('<%s>', THE_CONTENT)) ? 1 : 0;
        $nested = [...$nested, ...everyScrollViewInsideAScrollView($template, $scrolls)];
    }

    expect($read)->toBeGreaterThan(0, 'no template opens content, so this rule read nothing');

    expect($nested)->toBe([], sprintf(
        "These open a scroll view inside content that is already open:\n  %s\n\n"
        . 'Content is a scroll view, and so is every component that wraps itself in it. '
        . 'Inside content, a reading that did not come back is `<x-operator::what-stood-in-the-way>`, '
        . 'which draws the same lines with no container of its own.',
        implode("\n  ", $nested),
    ));
});
