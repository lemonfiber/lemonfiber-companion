<?php

declare(strict_types=1);

use Tests\Support\Edge;
use Tests\Support\Template;

// F3 — every EDGE class and tag verified against the installed package.
//
// This is the one failure mode in the application that nothing else catches.
// EDGE parses a class, finds it means nothing, and drops it: no error, no
// warning, no failed build. The screen renders, looks wrong, and says nothing
// about why — on a device, later, in front of whoever is holding the phone.
// `bg-theme-backgrnd` is the example in AGENTS.md for exactly that reason.
//
// The vocabulary is never written down here. It is asked of the parser and of
// the registries, so a NativePHP release that adds a utility makes it available
// the same day and one that removes a utility starts failing the same day. A
// transcribed list would keep passing through both, which is how a rule dies
// without anyone noticing.

$templates = Template::all();

// Not a guard against an empty repository — a guard against this file quietly
// checking nothing once there are screens to check.
it('has templates to check', function () use ($templates): void {
    expect($templates)->not->toBeEmpty();
})->skip($templates === [], 'No Blade template exists yet. These rules apply as soon as one does.');

foreach ($templates as $template) {
    it(sprintf('F3 — every class in %s is one EDGE knows', $template->path), function () use ($template): void {
        $dropped = Edge::unsupportedClasses($template->path, $template->classStrings());

        expect($dropped)->toBe([], sprintf(
            "EDGE does not know these and drops them:\n  %s\n\n"
            . 'A dropped class is not an error anywhere: the class is parsed, found to '
            . 'mean nothing, and discarded, so the screen renders without the style and '
            . 'nothing reports it. Check the spelling against the utilities the installed '
            . "package supports.\nA `bg-theme-*` token reported here is a token this "
            . 'surface deliberately does not assert: the design module maps the accent '
            . 'and its foreground, and leaves every other colour to the platform\'s own '
            . 'theme roles (F3, DES-R24).',
            implode("\n  ", $dropped),
        ));
    });

    it(sprintf('F3 — every tag in %s resolves to an element', $template->path), function () use ($template): void {
        $known = Edge::knownTags();

        $unresolvable = array_values(array_filter(
            $template->nativeTags(),
            static fn(string $tag): bool => ! in_array($tag, $known, strict: true),
        ));

        expect($unresolvable)->toBe([], sprintf(
            "These tags resolve to no element:\n  %s\n\n"
            . 'The collector throws `Unknown native element type` when the screen is '
            . 'opened, which on a device is a crash rather than a missing style. The tag '
            . 'is a collector builtin, a registered element, or a registered child '
            . 'component, and this one is none of them (F3).',
            implode("\n  ", $unresolvable),
        ));
    });

    it(sprintf('F3 — every element in %s is written with its prefix', $template->path), function () use ($template): void {
        $known = Edge::knownTags();

        $bare = array_values(array_filter(
            $template->bareTags(),
            static fn(string $tag): bool => in_array($tag, $known, strict: true),
        ));

        expect($bare)->toBe([], sprintf(
            "These render as native elements and do not look like it:\n  %s\n\n"
            . 'The precompiler builds a bare-tag allowlist from the element registry, so '
            . '`<column>` compiles exactly as `<native:column>` does. What it cannot do is '
            . 'tell a reader which of the two a tag is — and a `<button>` that looks like '
            . 'HTML in a file that compiles to native widgets is a tag somebody will edit '
            . 'as though it were HTML. Write the prefix (F3).',
            implode("\n  ", $bare),
        ));
    });
}
