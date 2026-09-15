<?php

declare(strict_types=1);

use Tests\Support\Screens;
use Tests\Support\Template;
use Tests\Support\Tree;

// F10 — a template only calls what the screen it belongs to publishes.
//
// Every other rule over these files reads them as text: what they say, what
// they iterate, what they name. Nothing read the one thing that makes a screen
// work — the join between `$this->something()` in the markup and a method on
// the class that renders it. Blade resolves that at render time, which is a
// phone, which is after everybody has gone home.
//
// It is not hypothetical. `WhatThisStackRuns` was written with an accessor per
// field and then collapsed to one that hands out the whole answer, which
// removed seven methods a template was calling. Planting a call to a method
// that does not exist passed all fifteen hundred tests in this suite: every
// screen could be calling something renamed two releases ago and the first
// report would be an operator looking at a crash.
//
// The join is read from `render()`, which is where a screen names its view, so
// a screen and a template are paired by what the code says rather than by a
// list here that would go stale the first time somebody renamed a file.

/**
 * Every method a template asks its own screen for.
 *
 * Only the first call in a chain: `$this->goes()->health()` asks this screen for
 * `goes` and asks whatever that answers for the rest, which is a different
 * class's business and not this rule's.
 *
 * @return list<string>
 */
function everyMethodATemplateAsksFor(Template $template): array
{
    preg_match_all('/\$this->([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $template->source, $asked);

    return array_values(array_unique($asked[1]));
}

it('F10 — every method a template calls is one its screen has', function (): void {
    $screens = Screens::byTheViewTheyRender();

    // Assert the reading before what it says. A mapping that found no screens
    // would make this pass about nothing, which is the state it exists to
    // refuse — and the state it was in before it existed.
    expect($screens)->not->toBe([], 'no screen was paired with a view, so this rule read nothing');

    $missing = [];

    foreach ($screens as $named => $screen) {
        $path = Tree::at(Screens::theFileBehindTheView($named));

        if (! is_file($path)) {
            $missing[] = sprintf('%s renders %s, and no template of that name exists', $screen->getShortName(), $named);

            continue;
        }

        $template = new Template($named, (string) file_get_contents($path));

        foreach (everyMethodATemplateAsksFor($template) as $method) {
            if (! $screen->hasMethod($method)) {
                $missing[] = sprintf('%s calls $this->%s(), and %s has no such method', $named, $method, $screen->getShortName());
            }
        }
    }

    expect($missing)->toBe([], sprintf(
        "These templates ask their screen for something it does not have:\n  %s\n\n"
        . 'Blade resolves this at render time, which is a phone, which is after everybody '
        . "has gone home. A method renamed on a screen renames nothing in its markup.\n",
        implode("\n  ", $missing),
    ));
});

it('F10 — every screen that renders is one this rule pairs', function (): void {
    // The other direction, and it is about the mapping rather than the markup:
    // a screen whose `render()` this could not read would be silently left out,
    // and a rule that quietly judges eight of ten files is the shape every
    // other rule here exists to refuse.
    $rendering = [];

    foreach (Screens::all() as $screen) {
        if (Screens::rendersAViewOfItsOwn($screen)) {
            $rendering[] = $screen->getShortName();
        }
    }

    sort($rendering);

    expect(count(Screens::byTheViewTheyRender()))->toBe(count($rendering), sprintf(
        "this rule paired %d screens and %d render: %s.\n"
        . 'The ones it could not pair are judged by nothing, which is worse than not having '
        . "the rule — the suite reports green for markup nobody read.\n",
        count(Screens::byTheViewTheyRender()),
        count($rendering),
        implode(', ', $rendering),
    ));
});
