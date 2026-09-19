<?php

declare(strict_types=1);

use Modules\Dx\Providers\DxServiceProvider;
use Modules\Kernel\Api\Stacks;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhereAScreenCanSendYou;

// F15 — every screen the router serves draws when it is drawn.
//
// `F10` and `F14` read the join between a template and its screen. `F12` reads
// the join between screens. Neither of them, and nothing else here, ever asks a
// screen to render — and rendering is where a frame is actually decided: the
// precompiler rewrites the native tags, the component tree resolves, the chrome
// is hoisted, and the screen's own state is passed to the view by the package
// rather than by anything this repository wrote.
//
// That last one had three screens broken. `native:model="typed"` expands to
// `:value="$typed"`, a bare variable in the compiled view, and `fromView()`
// fills the view's data from the component's **public** properties. The state
// these screens keep is `protected` — deliberately, and for a good reason — so
// `$typed` was undefined, on the device, on every frame of the two pairing
// screens and the sign-in screen. Every test passed: each reads `typed()` in
// PHP, which is a different question from what the view gets.
//
// So this builds each screen the way the application builds it — from the
// container, with the route's own parameters — and draws it.
//
// **With stand-ins on, because the alternative is a walk that proves less.**
// Every port answers, so each screen reaches the branch it draws when a machine
// answered rather than the one it draws when nothing did. It is also the claim
// `modules/dx` exists to make: the whole application, reachable, with no stack
// running anywhere.

/**
 * A machine to put in a route, from the stand-in the module seeded.
 *
 * Taken from the port rather than built here, so it is the stack the app would
 * actually be looking at — one assembled beside this would be a second opinion
 * about what the device holds.
 */
function theStackThisWalkLooksAt(): string
{
    foreach (app()->make(Stacks::class)->configured() as $stack) {
        return $stack->id()->stored();
    }

    throw new RuntimeException('The stand-in seeded no stack, so there is no machine to walk.');
}

/**
 * What the router was given for one screen, drawn.
 *
 * A named function because reflection and rendering both raise, and the
 * analyser refuses a checked exception inside a closure — rightly.
 *
 * @param array<string, string> $params
 */
function whatThisScreenDraws(string $class, array $params): WhatTheDeviceWouldDraw
{
    $screen = app()->make($class);

    if (! $screen instanceof NativeComponent) {
        throw new RuntimeException(sprintf('%s is served by the router and is not a screen.', $class));
    }

    if ($params !== []) {
        $screen->setParams($params);
    }

    return WhatTheDeviceWouldDraw::by($screen);
}

/**
 * Every screen a person can be sent to, and what its route needs filling in.
 *
 * Read off the two enums rather than off the paths, because the placeholders
 * are what a screen is handed and the paths have already had them filled in.
 * `alsoNeedsAService()` is the enum's own answer, so a case added with a second
 * placeholder is covered here without anybody remembering.
 *
 * @return array<string, array<string, string>>
 */
function everyRouteTheAppServes(string $stack): array
{
    $routes = [];

    foreach (AScreenWithoutAStack::cases() as $case) {
        $routes[sprintf('AScreenWithoutAStack::%s', $case->name)] = [];
    }

    foreach (AStacksScreen::cases() as $case) {
        $routes[sprintf('AStacksScreen::%s', $case->name)] = $case->alsoNeedsAService()
            ? ['stack' => $stack, 'service' => 'gluetun']
            : ['stack' => $stack];
    }

    return $routes;
}

it('F15 — every screen the router serves draws something when it is drawn', function (): void {
    config(['dx.stands_in' => true]);
    app()->register(new DxServiceProvider(app()), force: true);

    $served = WhereAScreenCanSendYou::read()->screensTheRouterServes();
    $routes = everyRouteTheAppServes(theStackThisWalkLooksAt());

    // The floor, and the one that matters: a walk over no screens passes.
    expect($routes)->not->toBe([]);

    $wrong = [];

    foreach ($routes as $case => $params) {
        if (! array_key_exists($case, $served)) {
            // Not a skip. A case the router serves nothing under is a screen
            // nobody can reach, and reading it as *nothing to draw* would be
            // this walk quietly getting smaller.
            $wrong[] = sprintf('%s — the router serves nothing under it, so nothing was drawn', $case);

            continue;
        }

        try {
            $drawn = whatThisScreenDraws($served[$case], $params);
        } catch (ErrorException|Error $why) {
            // The two a *drawing* fails with, named rather than caught broadly
            // (`C6`). A promoted warning — the undefined variable this rule was
            // written for — arrives as an `ErrorException`, and Laravel wraps a
            // view's own failure in one too. A wiring failure is neither, and
            // is left to come out of this test with its own message: a screen
            // the container cannot build is not a screen that drew badly.
            $wrong[] = sprintf('%s — %s: %s', $case, $why::class, $why->getMessage());

            continue;
        }

        if ($drawn->said() === []) {
            $wrong[] = sprintf('%s — drew a frame that says nothing at all', $case);
        }
    }

    sort($wrong);

    expect($wrong)->toBe([], sprintf(
        "These are screens a person can be sent to, and this is what happened when they were drawn:\n  %s\n\n"
        . 'A screen is decided at render time — the precompiler, the component tree, the chrome, '
        . "and the state the package passes to the view. None of that is visible in the markup.\n"
        . 'An undefined variable here is a warning rather than a stop, so the frame draws with '
        . 'the field empty and nothing anywhere says why.',
        implode("\n  ", $wrong),
    ));
});
