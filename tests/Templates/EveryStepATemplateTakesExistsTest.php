<?php

declare(strict_types=1);

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
 * @return list<array{method: string, steps: list<string>, said: string}>
 */
function everyStepATemplateTakes(Template $template): array
{
    preg_match_all(
        '/\$this->([a-zA-Z_]\w*)\([^()]*\)((?:->[a-zA-Z_]\w*(?:\([^()]*\))?)+)/',
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
 * Where one chain steps onto something that is not there, and how far it got.
 *
 * A function rather than the body of the loop below, because reflection raises
 * a checked exception and the analyser refuses one thrown inside a closure —
 * rightly: a closure has nowhere to declare it.
 *
 * @param  ReflectionClass<object>                                  $screen
 * @param  array{method: string, steps: list<string>, said: string} $chain
 * @return array{said: ?string, followed: int}
 */
function whereThisChainStopsBeingTrue(ReflectionClass $screen, array $chain, string $named): array
{
    $holding = whateverThatTypeIs($screen->getMethod($chain['method'])->getReturnType());
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

            $went = whereThisChainStopsBeingTrue($screen, $chain, $named);
            $followed += $went['followed'];

            if (is_string($went['said'])) {
                $missing[] = $went['said'];
            }
        }
    }

    // The floor `Q-R66` asks for, and the one that matters most here: the whole
    // rule is a walk of declared types, and a walk that resolves nothing
    // reports nothing. A regex that stopped matching, a screen whose return
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
