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

/**
 * Every property a template binds with `native:model`.
 *
 * The precompiler's own pattern, modifiers and all, so a binding this reads is
 * exactly a binding the package expands — a looser one would judge markup the
 * package never treats as a binding, and a stricter one would skip some that it
 * does.
 *
 * @return list<string>
 */
function everyPropertyATemplateBinds(Template $template): array
{
    preg_match_all('/native:model(\.[a-zA-Z0-9.]+)?=["\']([^"\']+)["\']/', $template->source, $bound);

    return array_values(array_unique($bound[2]));
}

/**
 * Every property a binding can reach on a screen: public, and not static.
 *
 * Read as a list rather than asked of one name, because the list cannot throw
 * and asking for a property that is not there can — and the answer this rule
 * wants for a missing one is *not kept*, not an exception.
 *
 * @param  ReflectionClass<object> $screen
 * @return list<string>
 */
function theStateABindingCanReach(ReflectionClass $screen): array
{
    $kept = [];

    foreach ($screen->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
        if (! $property->isStatic()) {
            $kept[] = $property->getName();
        }
    }

    return $kept;
}

it('F10 — every property a template binds is one its screen keeps where a binding can reach it', function (): void {
    // The same join as the case above, for state rather than for a method, and
    // it is the one that became silent. `nativephp/mobile` 4.4.1 expanded a
    // binding to a bare variable, so a name the screen did not keep was an
    // undefined variable and rendering raised. 4.5 expands it to
    // `data_get(get_defined_vars(), …)`, which answers null for a name nothing
    // supplies: the field draws empty, every keystroke is synced to a property
    // that does not exist, and nothing anywhere raises. Read here instead, off
    // the markup and the class, so the answer does not depend on which version
    // of the package happens to be drawing.
    //
    // Public and not static, because that is what the package reaches from
    // both ends: it fills the view from public properties and, since 4.5.1,
    // writes back only to public, non-static ones.
    $screens = Screens::byTheViewTheyRender();

    expect($screens)->not->toBe([], 'no screen was paired with a view, so this rule read nothing');

    $missing = [];
    $read = 0;

    foreach ($screens as $named => $screen) {
        $path = Tree::at(Screens::theFileBehindTheView($named));

        if (! is_file($path)) {
            // The case above names this, and naming it twice is noise.
            continue;
        }

        foreach (everyPropertyATemplateBinds(new Template($named, (string) file_get_contents($path))) as $property) {
            $read++;

            if (! in_array($property, theStateABindingCanReach($screen), strict: true)) {
                $missing[] = sprintf(
                    '%s binds native:model="%s", and %s keeps no public property of that name',
                    $named,
                    $property,
                    $screen->getShortName(),
                );
            }
        }
    }

    // A walk that read no binding would pass about nothing. The pairing and
    // sign-in screens bind their fields, so none read means the pattern or the
    // pairing stopped finding them.
    expect($read)->toBeGreaterThan(0, 'no native:model binding was read, so this rule judged nothing')
        ->and($missing)->toBe([], sprintf(
            "These templates bind state their screen does not keep where a binding can reach it:\n  %s\n\n"
            . 'Since nativephp/mobile 4.5 a binding is read through data_get, so a name nothing supplies '
            . 'draws an empty field and raises nothing, on a phone or here. Every keystroke is then '
            . "synced to a property that does not exist.\n",
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
