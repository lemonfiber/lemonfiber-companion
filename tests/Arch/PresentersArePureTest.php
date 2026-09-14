<?php

declare(strict_types=1);

use Tests\Support\Module;

// F2 — a presenter is pure: data in, view model out.
//
// This is where 100% coverage and mutation testing actually land, because it is
// where the decisions are. A presenter that holds a port stops being a function
// of its arguments: the test has to arrange the port before it can ask the
// question, and the thing being measured becomes the fake rather than the
// decision. It also puts IO one constructor away from the render path, on a
// device where the answer may take thirty seconds to arrive.
//
// The check is "no interface in the constructor" rather than a list of known
// port names. Every port is an interface by definition, a new one added
// tomorrow is caught without anyone updating a list here, and the things a
// presenter legitimately takes — values, typed collections, view models — are
// final readonly classes and pass.

/**
 * The presenters among a list of class names.
 *
 * The selection is a function rather than a condition written inline, because
 * the rule below and the guard beneath it have to be looking at the same set.
 * A guard that asserted its own separate answer was not empty would report a
 * healthy rule while the rule narrowed to nothing.
 *
 * @param  list<class-string> $names
 * @return list<class-string>
 */
function thePresentersAmong(array $names): array
{
    return array_values(array_filter(
        $names,
        static fn(string $name): bool => str_contains($name, '\Presenters\\'),
    ));
}

/**
 * Every presenter this repository declares.
 *
 * @return list<class-string>
 */
function everyPresenter(): array
{
    $found = [];

    foreach (Module::all() as $module) {
        $found = [...$found, ...thePresentersAmong($module->classNames())];
    }

    return $found;
}

/**
 * Every screen this repository declares.
 *
 * Read the same way presenters are, because the two counts are compared and a
 * comparison between a discovered number and a written one is a comparison
 * against whatever somebody last typed.
 *
 * @return list<class-string>
 */
function everyScreen(): array
{
    $found = [];

    foreach (Module::all() as $module) {
        $found = [...$found, ...array_values(array_filter(
            $module->classNames(),
            static fn(string $name): bool => str_contains($name, '\Screens\\'),
        ))];
    }

    return $found;
}

it('F2 — a presenter is handed data, not a way to go and get it', function (): void {
    $offenders = [];

    foreach (everyPresenter() as $name) {
        $constructor = new ReflectionClass($name)->getConstructor();

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            if (interface_exists($type->getName())) {
                $offenders[] = sprintf('%s takes $%s (%s)', $name, $parameter->getName(), $type->getName());
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These presenters were injected with something to call:\n  %s\n\n"
        . 'A presenter takes the answer, not the means of getting it. Move the call to '
        . 'the component that already knows it is on a screen, and hand the presenter '
        . 'what came back (F2).',
        implode("\n  ", $offenders),
    ));
});

// The half that fails by name when there is nothing left to judge.
//
// The rule above iterates a discovered set, and discovery has one failure mode
// that is indistinguishable from compliance: it finds nothing, there is nothing
// to refuse, and a green tick is what that looks like. This rule has already
// spent its whole life in that state — `Presenters/` did not exist, so the
// filter matched no class and F2 passed every run having read nothing.
//
// `Q-R66` asserts the foundations every rule shares and says in the same breath
// that a rule narrowing to a namespace nobody uses is each rule's own business.
// This is F2's, and it is kept beside F2 rather than there for that reason: the
// two must move together if the directory is ever renamed.

it('F2 — the presenters the rule judges are found', function (): void {
    // A floor rather than a count, because an exact number is a number somebody
    // edits to make a red run green — and what is worth catching is not one
    // presenter arriving or leaving, it is the set collapsing, which is what a
    // renamed directory or a namespace nothing registers does to it.
    //
    // The floor is the number of screens rather than a figure written here, and
    // it is the architecture's own claim counted: a surface "delegates every
    // decision to a presenter", so a repository with more screens than
    // presenters has screens deciding things for themselves. Derived, so it
    // needs no maintenance and cannot be lowered to make a run go green — the
    // only way to lower it is to delete a screen.
    //
    // The screen count is asserted first for the reason this whole rule exists:
    // a floor of zero is satisfied by everything, so a discovery failure in
    // `everyScreen()` would make the comparison beneath it vacuous in exactly
    // the way F2 itself was.
    expect(everyScreen())->not->toBe([]);

    expect(everyPresenter())->not->toBe([])
        ->and(count(everyPresenter()))->toBeGreaterThanOrEqual(count(everyScreen()));
});

it('F2 — the selection is watched refusing', function (): void {
    // The assertion above holds just as well for a selection that says yes to
    // everything, and that is the failure this whole file is about. So the
    // filter is handed the two shapes it has to tell apart: a screen and a view
    // model sit one directory away from a presenter and must not be read as
    // one, or F2 would be judging the mutable, port-holding classes it exists
    // to keep presenters distinct from.
    expect(thePresentersAmong([
        'Modules\Operator\Internal\Presenters\HowAStackReads',
        'Modules\Operator\Internal\Screens\HowThisStackIs',
        'Modules\Operator\Internal\ViewModels\WhatTheStackTurnedOutToBe',
    ]))->toBe(['Modules\Operator\Internal\Presenters\HowAStackReads']);

    expect(thePresentersAmong([]))->toBe([]);
});
