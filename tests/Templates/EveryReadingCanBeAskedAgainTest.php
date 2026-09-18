<?php

declare(strict_types=1);

use Modules\Operator\View\Components\WhatStoppedTheReading;
use Tests\Support\Screens;
use Tests\Support\Tree;

// A screen that can be asked again offers it where the reading came back.
//
// The requirement is that a screen must not rely on being left and returned to.
// Five screens had `again()` and no way to reach it: the retry lived on the
// obstacle arm, drawn by the component that reports what stopped a reading, so
// a reading that *failed* could be tried again and a reading that *came back*
// could not.
//
// That is the wrong way round. An operator watching a stuck download clear, or
// an update land, or a service go quiet is looking at a screen they want to ask
// again — and the one state where asking again is least interesting is the one
// where nothing came back at all.
//
// Nothing else could see it. `F10` reads the other direction — a template
// naming a method its screen has not got — and a method no template names is
// invisible to it. The screens passed every rule they had, and the control was
// simply not on the frame.

/**
 * Every screen that can be asked again, and what its own markup says.
 *
 * The screen's own file rather than the composed one. The obstacle component
 * carries `again()` as its default retry, so a composed reading would find the
 * call on every screen that draws the component — which is every screen here,
 * and is the exact state this rule exists to refuse.
 *
 * @return array<string, string> view name => the screen's own markup
 */
function whatEachScreenThatCanBeAskedAgainSays(): array
{
    $asking = [];

    foreach (Screens::byTheViewTheyRender() as $view => $screen) {
        if (! $screen->hasMethod('again')) {
            continue;
        }

        $path = Tree::at(Screens::theFileBehindTheView($view));

        if (is_file($path)) {
            $asking[$view] = (string) file_get_contents($path);
        }
    }

    return $asking;
}

/**
 * How the obstacle arm spells *ask again*.
 *
 * Read off the component rather than written here, because one spelling is the
 * whole point: the component offers a retry where a reading did not come back
 * and each screen offers the same one where it did, and a rule holding its own
 * copy of the word would go quiet the day somebody renamed the method.
 *
 * The default rather than what a screen passes in. Nothing passes one, and a
 * screen that did would be saying its retry is a different act from the
 * component's — which is a decision, and one this rule should report rather
 * than quietly accept.
 */
function theRetryTheObstacleArmOffers(): string
{
    $handed = new ReflectionClass(WhatStoppedTheReading::class)
        ->getConstructor()
        ?->getParameters() ?? [];

    foreach ($handed as $parameter) {
        if ($parameter->getName() !== 'askAgain' || ! $parameter->isDefaultValueAvailable()) {
            continue;
        }

        $spelled = $parameter->getDefaultValue();

        // Narrowed rather than cast. A default that is not a string is a
        // component whose retry is no longer a method name, and casting it
        // would hand this rule the word `Array` to look for on every screen —
        // which every screen would fail, naming the wrong thing.
        if (is_string($spelled)) {
            return $spelled;
        }
    }

    throw new RuntimeException('The obstacle arm no longer carries a default retry, so no screen can offer the same one.');
}

it('finds screens that can be asked again', function (): void {
    // The floor every rule of this shape owes: a reading that found none passes
    // the rule below with no iterations, which is what it would do on the day
    // `byTheViewTheyRender()` went quiet.
    expect(count(whatEachScreenThatCanBeAskedAgainSays()))->toBeGreaterThan(3);
});

it('N1-R27 — a screen that can be asked again says so on its own frame', function (): void {
    $unreachable = [];

    foreach (whatEachScreenThatCanBeAskedAgainSays() as $view => $said) {
        if (! str_contains($said, theRetryTheObstacleArmOffers())) {
            $unreachable[] = $view;
        }
    }

    sort($unreachable);

    expect($unreachable)->toBe([], sprintf(
        "These can be asked again and never offer it:\n  %s\n\n"
        . 'The retry on the obstacle arm is the component\'s, and it is drawn only where a '
        . "reading did not come back — so a screen with nothing else is one an operator can "
        . "refresh by failing and not by asking.\n"
        . '`N1-R27` is what that leaves: a screen relying on being left and returned to, '
        . 'which on a handset is the gesture that works differently on every one of them.',
        implode("\n  ", $unreachable),
    ));
});
