<?php

declare(strict_types=1);

use Tests\Support\OneDestination;
use Tests\Support\Template;

// F7, G4-R8 — a template reads nothing that has one destination.
//
// `G4-R8` is that an error must carry no credential and no secret, and this
// holds it by being stricter: the three values are refused on every screen,
// which includes the ones an operator only ever sees because something failed.
// The implication runs one way — narrow this to a subset of screens and
// `G4-R8` stops being held, with no rule going red — so it is named here rather
// than left to be rediscovered.
//
//
// N1-R15, N1-R8 and N1-R7 name the three values, and the one destination each
// has is never a screen.
//
// Each of the three publishes exactly one accessor, named for where its value
// goes: a session goes in a header, a credential goes to the exchange, an
// address goes to the client. `AValueWithOneDestinationTest` holds them to
// publishing only that one. What it cannot see is who *calls* it.
//
// A template is where that matters and is the one place nothing else looks.
// PHPStan does not read Blade; the architecture rules reflect over classes and a
// template is neither; `TypesThatMustNotMeetTest` refuses a screen that is
// *handed* one of these types and cannot refuse a screen that reaches one
// through a value it legitimately holds. `{{ $stack->at()->forTheClient() }}`
// passes every other gate in this repository and puts a stack's address on the
// glass — which is exactly what an operator with two stacks would reach for to
// tell them apart, and exactly what `N1-R11` says the name is for.
//
// The list is not written here. It is the same table
// `AValueWithOneDestinationTest` counts against, so an accessor renamed there is
// renamed here — a rule naming a method that no longer exists finds nothing and
// reports a clean run, which is the shape of every dead rule this suite has had
// to find.

$templates = Template::all();

it('has templates to check', function () use ($templates): void {
    expect($templates)->not->toBeEmpty();
})->skip($templates === [], 'No Blade template exists yet. This rule applies as soon as one does.');

foreach ($templates as $template) {
    it(sprintf('F7 — %s reads nothing that has one destination', $template->path), function () use ($template): void {
        $reached = [];

        foreach (OneDestination::all() as [$type, $accessor, $rule, $why]) {
            if (! str_contains($template->source, sprintf('%s(', $accessor))) {
                continue;
            }

            $reached[] = sprintf('%s calls %s::%s() — %s: %s', $template->path, $type, $accessor, $rule, $why);
        }

        expect($reached)->toBe([], sprintf(
            "These put a value on screen that has exactly one place to go, and it is not a screen:\n  %s\n\n"
            . 'The accessor is named for where the value goes, which is what makes a second '
            . "use read wrong where it is written — and a template is where nothing else is\n"
            . 'looking: PHPStan does not read Blade, and the architecture rules reflect over '
            . "classes.\nAn operator tells two stacks apart by the name they chose (N1-R11), "
            . 'not by an address.',
            implode("\n  ", $reached),
        ));
    });
}
