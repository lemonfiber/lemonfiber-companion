<?php

declare(strict_types=1);

use Tests\Support\Components;
use Tests\Support\Screens;
use Tests\Support\Template;
use Tests\Support\Tree;

// F14 — a template only steps onto what the value it was answered with has.
//
// `F10` reads the first call, which is half the join. The other half is what a
// template does with the answer: `$this->answer()->went->met` asks the screen
// for one thing and then takes two steps off it, and neither step is anything
// `F10` looks at.
//
// A missing property is quieter than a missing method. PHP raises a warning and
// evaluates to null, so `$this->done()->met !== ''` on a value that no longer
// has `met` is not a crash — it is `true`, on every frame, and the screen draws
// its obstacle branch over a stack that answered perfectly well.
//
// That is not hypothetical. Nine view models had `met`, `remedy` and
// `isSignedOut` folded into one `went` value; every template was rewritten and
// one `@elseif` was missed. The suite was green: the screen's own tests read
// `done()->went->met` in PHP, and every template rule reads Blade as text.
//
// The chain is walked by declared type — the screen's return type, then each
// property's — which is what makes it a fact about the code rather than a list
// here that goes stale.
//
// Components are walked too, from the other end. A screen's chain starts at a
// method it declares; a component's starts at a property it was handed, and
// `$went->met` in a component's markup drifts exactly as quietly. Components
// are the worse half, in fact: one of them stands on seven screens.

/**
 * Every step a template takes after its own screen has answered.
 *
 * Rooted at `$this->something()` on purpose. A loop variable's type is decided
 * by whatever the collection holds, which this cannot see, and a rule that
 * guessed would report on a class the template never touched.
 *
 * A step carries its own parentheses where it is a call, so the walk knows
 * which of the two questions to ask of the class it is holding. `F10` owns the
 * first step and this owns every step after it.
 *
 * **An argument may hold a call of its own**, and the parentheses have to be
 * counted rather than skipped to. `$this->goes()->logsOf($this->thing()->service->id)`
 * read with a flat `[^()]*` stops at the inner `(`, so the call reads as a
 * property and the rule reports a method that is there as a field that is not.
 * One level of nesting is what a template writes; deeper than that is a step
 * this drops rather than misreads.
 *
 * @return list<array{method: string, steps: list<string>, said: string}>
 */
function everyStepATemplateTakes(Template $template): array
{
    preg_match_all(
        '/\$this->([a-zA-Z_]\w*)\((?:[^()]|\([^()]*\))*\)((?:->[a-zA-Z_]\w*(?:\((?:[^()]|\([^()]*\))*\))?)+)/',
        $template->source,
        $found,
        PREG_SET_ORDER,
    );

    $walked = [];

    foreach ($found as [$said, $method, $chain]) {
        $walked[$said] = [
            'method' => $method,
            'steps' => array_values(array_filter(
                explode('->', $chain),
                static fn(string $step): bool => $step !== '',
            )),
            'said' => $said,
        ];
    }

    return array_values($walked);
}

/**
 * The class a declared type names, or nothing where it names no class.
 *
 * `string`, `array` and `bool` all arrive here and all answer nothing, which is
 * the right answer: a field read off one of them is a chain this rule cannot
 * follow, and the walk stops rather than guessing.
 *
 * @return ReflectionClass<object>|null
 */
function whateverThatTypeIs(?ReflectionType $type): ?ReflectionClass
{
    if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
        return null;
    }

    $named = $type->getName();

    return class_exists($named) ? new ReflectionClass($named) : null;
}

/** Whether a step is a call rather than a field — it carries its own parentheses. */
function aTemplateStepIsACall(string $step): bool
{
    return str_contains($step, '(');
}

/** A step's name, with the parentheses taken off a call. */
function whatATemplateStepIsCalled(string $step): string
{
    return aTemplateStepIsACall($step)
        ? mb_substr($step, 0, (int) mb_strpos($step, '('))
        : $step;
}

/**
 * Whether the class being held has the step about to be taken.
 *
 * @param ReflectionClass<object> $holding
 */
function aClassHasThatTemplateStep(ReflectionClass $holding, string $step): bool
{
    $name = whatATemplateStepIsCalled($step);

    return aTemplateStepIsACall($step) ? $holding->hasMethod($name) : $holding->hasProperty($name);
}

/**
 * What the walk holds after taking that step, or nothing where it leaves the
 * world of classes behind.
 *
 * Guarded by {@see aClassHasThatTemplateStep()} at the one call site, so the
 * checked exception reflection declares cannot arise.
 *
 * @param  ReflectionClass<object>      $holding
 * @return ReflectionClass<object>|null
 */
function theClassAfterATemplateStep(ReflectionClass $holding, string $step): ?ReflectionClass
{
    $name = whatATemplateStepIsCalled($step);

    return whateverThatTypeIs(aTemplateStepIsACall($step)
        ? $holding->getMethod($name)->getReturnType()
        : $holding->getProperty($name)->getType());
}

/** How a step is written where it has to be named in a report. */
function howATemplateStepReads(string $step): string
{
    return aTemplateStepIsACall($step)
        ? sprintf('%s()', whatATemplateStepIsCalled($step))
        : sprintf('$%s', $step);
}

/**
 * What a screen answers a call with, as a class, or nothing where it is not one.
 *
 * Guarded by `hasMethod()` at its call site, so the checked exception
 * reflection declares cannot arise — which the analyser can only be told by the
 * call sitting outside a closure.
 *
 * @param ReflectionClass<object> $screen
 * @return ReflectionClass<object>|null
 */
function whatThatScreenAnswersWith(ReflectionClass $screen, string $method): ?ReflectionClass
{
    return whateverThatTypeIs($screen->getMethod($method)->getReturnType());
}

/**
 * Where one chain steps onto something that is not there, and how far it got.
 *
 * A function rather than the body of the loop below, because reflection raises
 * a checked exception and the analyser refuses one thrown inside a closure —
 * rightly: a closure has nowhere to declare it.
 *
 * Takes what the walk starts holding rather than working it out, because the
 * two callers start differently: a screen's chain opens on what a method
 * answered, and a component's opens on the component itself.
 *
 * @param  ReflectionClass<object>|null                             $holding
 * @param  array{method: string, steps: list<string>, said: string} $chain
 * @return array{said: ?string, followed: int}
 */
function whereThisChainStopsBeingTrue(?ReflectionClass $holding, array $chain, string $named): array
{
    $followed = 0;

    foreach ($chain['steps'] as $step) {
        if (! $holding instanceof ReflectionClass) {
            break;
        }

        if (! aClassHasThatTemplateStep($holding, $step)) {
            return [
                'said' => sprintf(
                    '%s reads `%s`, and %s has no `%s`',
                    $named,
                    $chain['said'],
                    $holding->getShortName(),
                    howATemplateStepReads($step),
                ),
                'followed' => $followed,
            ];
        }

        $followed++;
        $holding = theClassAfterATemplateStep($holding, $step);
    }

    return ['said' => null, 'followed' => $followed];
}

it('F14 — every step a template takes after its screen answered is one that value has', function (): void {
    $screens = Screens::byTheViewTheyRender();

    expect($screens)->not->toBe([], 'no screen was paired with a view, so this rule read nothing');

    $missing = [];
    $followed = 0;

    foreach ($screens as $named => $screen) {
        $path = Tree::at(Screens::theFileBehindTheView($named));

        if (! is_file($path)) {
            continue;
        }

        $template = new Template($named, (string) file_get_contents($path));

        foreach (everyStepATemplateTakes($template) as $chain) {
            if (! $screen->hasMethod($chain['method'])) {
                // `F10`'s business, and reported there. Carrying on here would
                // name the same line in two rules and make the second one's
                // count depend on the first one's failures.
                continue;
            }

            $went = whereThisChainStopsBeingTrue(
                whatThatScreenAnswersWith($screen, $chain['method']),
                $chain,
                $named,
            );
            $followed += $went['followed'];

            if (is_string($went['said'])) {
                $missing[] = $went['said'];
            }
        }
    }

    // The floor every rule of this shape owes, and the one that matters most
    // here: the whole rule is a walk of declared types, and a walk that
    // resolves nothing reports nothing. A regex that stopped matching, a screen whose return
    // types went to `mixed` — either is a green run over markup nobody read.
    expect($followed)->toBeGreaterThan(20);

    sort($missing);

    expect($missing)->toBe([], sprintf(
        "These templates step onto something the value they were answered with has not got:\n  %s\n\n"
        . 'A missing field warns and evaluates to null rather than stopping, so the branch it '
        . "sits in is decided by a field nobody has — usually the wrong way round. A missing "
        . "method is a crash instead, and both happen at render time, which is a phone.\n"
        . 'Every other template rule reads the markup as text, so nothing else will say so.',
        implode("\n  ", $missing),
    ));
});

/**
 * Every step a component's markup takes off something it was handed.
 *
 * Rooted at a bare variable rather than at `$this`, because that is how a
 * component receives everything it draws. Which of those variables is a
 * property the component declares is decided by the caller — `$slot`,
 * `$attributes` and a loop's own variable are all bare variables too, and none
 * of them is this rule's business.
 *
 * @return list<array{method: string, steps: list<string>, said: string}>
 */
function everyStepAComponentTakes(Template $template): array
{
    preg_match_all(
        '/(?<![\w$>])\$([a-z]\w*)((?:->[a-zA-Z_]\w*(?:\((?:[^()]|\([^()]*\))*\))?)+)/',
        $template->source,
        $found,
        PREG_SET_ORDER,
    );

    $walked = [];

    foreach ($found as [$said, $held, $chain]) {
        if ($held === 'this') {
            continue;
        }

        $walked[$said] = [
            'method' => $held,
            'steps' => array_values(array_filter(
                explode('->', $chain),
                static fn(string $step): bool => $step !== '',
            )),
            'said' => $said,
        ];
    }

    return array_values($walked);
}

/**
 * The type a component declares for one of the things it is handed.
 *
 * Guarded by `hasProperty()` at its call site, and outside the closure for the
 * reason {@see whatThatScreenAnswersWith()} is.
 *
 * @param  ReflectionClass<object>      $component
 * @return ReflectionClass<object>|null
 */
function whatThatComponentWasHanded(ReflectionClass $component, string $held): ?ReflectionClass
{
    return whateverThatTypeIs($component->getProperty($held)->getType());
}

it('F14 — every step a component takes is one the value it was handed has', function (): void {
    $components = Components::byTheViewTheyRender();

    expect($components)->not->toBe([], 'no component was paired with a view, so this rule read nothing');

    $missing = [];
    $followed = 0;

    foreach ($components as $named => $component) {
        $path = Tree::at(Screens::theFileBehindTheView($named));

        if (! is_file($path)) {
            continue;
        }

        $template = new Template($named, (string) file_get_contents($path));

        foreach (everyStepAComponentTakes($template) as $chain) {
            if (! $component->hasProperty($chain['method'])) {
                // A loop's variable, a slot, or something a `@php` block made.
                // None of the three has a declared type this can read, and a
                // rule that guessed at one would report on a class the markup
                // never touched.
                continue;
            }

            $went = whereThisChainStopsBeingTrue(
                whatThatComponentWasHanded($component, $chain['method']),
                $chain,
                $named,
            );

            $followed += $went['followed'];

            if (is_string($went['said'])) {
                $missing[] = $went['said'];
            }
        }
    }

    // Lower than the screens' floor and meaning the same thing. There are nine
    // components and most draw only what they are given, so the number is small
    // by nature — what it refuses is a walk that resolves nothing at all.
    expect($followed)->toBeGreaterThan(3);

    sort($missing);

    expect($missing)->toBe([], sprintf(
        "These components step onto something the value they were handed has not got:\n  %s\n\n"
        . 'A component stands on every screen that names it, so a field renamed underneath '
        . "one is a line that goes wrong in several places at once.\n"
        . 'PHP warns and evaluates to null rather than stopping, so the branch it sits in is '
        . 'decided by a field nobody has.',
        implode("\n  ", $missing),
    ));
});
