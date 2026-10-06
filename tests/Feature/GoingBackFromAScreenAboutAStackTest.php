<?php

declare(strict_types=1);

use Modules\Dx\Api\AStandInStack;
use Modules\Dx\Providers\DxServiceProvider;
use Modules\Operator\Internal\WhereTheTabsAreDrawn;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\TheTabs;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\UI\Builders\Drawer;
use Tests\Support\WhatTheRouterHolds;
use Tests\Support\WhereAScreenCanSendYou;

// A screen about a stack has the platform's back control, with the menu's
// beside it, wherever the router holds a screen beneath it: offered on a
// screen pushed over another, never at the bottom of the stack, and never on a
// tab, which takes the place of another rather than lying over it.

/**
 * Every screen about a stack that carries the menu, by its case, built from the container against a stand-in that answers.
 *
 * @return array<string, array{AStacksScreen, NativeComponent}>
 */
function everyScreenAboutAStackWithItsMenu(): array
{
    config(['dx.stands_in' => true]);
    app()->register(new DxServiceProvider(app()), force: true);

    $served = WhereAScreenCanSendYou::read()->screensTheRouterServes();
    $stack = AStandInStack::Answering->asAStack()->id()->stored();
    $screens = [];

    foreach (AStacksScreen::cases() as $case) {
        $class = $served[sprintf('AStacksScreen::%s', $case->name)] ?? '';
        $screen = $class === '' ? null : app()->make($class);

        if (! $screen instanceof NativeComponent || ! method_exists($screen, 'drawerOverride')) {
            continue;
        }

        $screen->setParams($case->alsoNeedsAService() ? ['stack' => $stack, 'service' => 'gluetun'] : ['stack' => $stack]);
        $screens[$case->name] = [$case, $screen];
    }

    return $screens;
}

/** The path a screen about the stand-in stack is held at. */
function wherePushedOverAnother(AStacksScreen $case): string
{
    return str_replace(['{stack}', '{service}'], [AStandInStack::Answering->asAStack()->id()->stored(), 'gluetun'], $case->value);
}

/** Whether the screen's menu control sits beside a back button, held over this many screens. */
function offersAWayBackOver(NativeComponent $screen, AStacksScreen $case, int $beneathIt): bool
{
    $tab = AStacksScreen::Health->forTheStack(AStandInStack::Answering->asAStack()->id());
    WhatTheRouterHolds::over($screen, wherePushedOverAnother($case), ...array_fill(0, $beneathIt, $tab));

    $menu = method_exists($screen, 'drawerOverride') ? $screen->drawerOverride() : null;

    return $menu instanceof Drawer && $menu->isBesideBack();
}

/** Whether a screen is drawn as one of the operator's tabs. */
function isDrawnAsATab(NativeComponent $screen): bool
{
    $tabs = [];

    foreach (TheTabs::cases() as $tab) {
        $tabs[] = WhereTheTabsAreDrawn::classOf($tab);
    }

    return in_array($screen::class, $tabs, strict: true);
}

it('offers the way back on every screen about a stack pushed over another, but a tab', function (): void {
    $without = [];
    $looked = 0;

    foreach (everyScreenAboutAStackWithItsMenu() as $name => [$case, $screen]) {
        if (isDrawnAsATab($screen)) {
            continue;
        }

        $looked++;

        if (! offersAWayBackOver($screen, $case, beneathIt: 1)) {
            $without[] = $name;
        }
    }

    expect($looked)->toBeGreaterThan(10, 'no screen about a stack was looked at')
        ->and($without)->toBe([], 'These were pushed over another screen and offer no way back.');
});

it('offers no way back on a screen about a stack at the bottom of the stack, signing in among them', function (): void {
    $screens = everyScreenAboutAStackWithItsMenu();
    $with = [];

    foreach ($screens as $name => [$case, $screen]) {
        if (offersAWayBackOver($screen, $case, beneathIt: 0)) {
            $with[] = $name;
        }
    }

    expect(array_keys($screens))->toContain(AStacksScreen::SignIn->name)
        ->and($with)->toBe([], 'These have nothing beneath them and offer a way back all the same.');
});

it('never offers the way back on a tab, whatever the router holds beneath it', function (): void {
    $with = [];
    $tabs = 0;

    foreach (everyScreenAboutAStackWithItsMenu() as $name => [$case, $screen]) {
        if (! isDrawnAsATab($screen)) {
            continue;
        }

        $tabs++;

        foreach ([0, 1, 2] as $beneathIt) {
            if (offersAWayBackOver($screen, $case, $beneathIt)) {
                $with[] = sprintf('%s over %d', $name, $beneathIt);
            }
        }
    }

    expect($tabs)->toBe(count(TheTabs::cases()))
        ->and($with)->toBe([], 'These tabs offer a way back.');
});
