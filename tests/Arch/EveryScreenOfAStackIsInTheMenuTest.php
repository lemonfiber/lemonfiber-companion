<?php

declare(strict_types=1);

use Modules\Household\Internal\Screens\WhatIsOnLiveTv;
use Modules\Household\Internal\WhereTheTabsAreDrawn;
use Modules\Operator\Internal\Screens\YourStacks;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\TheHouseholdsTabs;
use Modules\Wayfinding\Api\TheTabs;
use Modules\Wayfinding\Internal\TheMenu;
use Modules\Wayfinding\Internal\TheStacksSettingsInTheMenu;
use Tests\Support\Tree;

// F18 — every screen of one stack is an operator's tab, a menu item, a
// member's tab, or a step of another.
//
// A member's tab is the tab and every screen it draws over itself: a screen
// `WhereTheTabsAreDrawn` puts under a household tab is that tab's, the way a
// title's page is Home's. Which screen the router serves for a case is read
// off the providers' `Router::native` lines, so this stays a reading of the
// source rather than of a booted application.
//
// Read from the screens a stack has, so a screen added tomorrow is covered the
// moment it exists. A screen that needs more than the stack — one service, one
// copy, one word — is opened from the screen that shows that thing, and is not
// asked about. A step is a screen reached only from the screen that begins it,
// and a detail a screen reached from the screen whose one thing it details;
// each is named below with where that is. The list may shrink, and grows only
// where the spec's menu page names another.

/** The steps and details reached from another screen rather than the menu, and from where. */
const REACHED_FROM_THEIR_SCREEN = [
    'Guard' => 'guarding where the data is kept is begun from After a restart, and held while that screen asks',
    'Copy' => 'taking a copy is begun from Backups, and from Uninstall',
    'Reset' => 'putting the configuration back is begun from General',
    'SignIn' => 'signing in is offered by the screen that found the session ended',
    'Versions' => 'Versions details what About says is running, and is reached from About',
];

/**
 * The class the router serves under each case of a stack's screens, read off every provider's `Router::native` line.
 *
 * @return array<string, string> case name => class
 */
function theClassUnderEachScreen(): array
{
    $served = [];

    foreach (Tree::filesUnder(Tree::at('app-modules'), 'ServiceProvider.php') as $provider) {
        $source = (string) file_get_contents($provider);
        preg_match_all('/^use ([\w\\\\]+);$/m', $source, $imports);
        $byShortName = [];

        foreach ($imports[1] as $import) {
            $byShortName[basename(str_replace('\\', '/', $import))] = $import;
        }

        preg_match_all('/Router::native\(AStacksScreen::(\w+)->value, (\w+)::class\)/', $source, $routes, PREG_SET_ORDER);

        foreach ($routes as [, $case, $class]) {
            $served[$case] = $byShortName[$class] ?? $class;
        }
    }

    return $served;
}

/**
 * The screens of one stack that no tab, menu item or step covers, given the class served under each.
 *
 * @param  array<string, string> $served case name => class
 * @return list<string>
 */
function theScreensStrandedWhenServing(array $served): array
{
    $covered = [
        ...array_map(static fn(TheMenu $item): string => $item->screen()->name, TheMenu::cases()),
        ...array_map(static fn(TheTabs $tab): string => $tab->screen()->name, TheTabs::cases()),
        ...array_map(static fn(TheHouseholdsTabs $tab): string => $tab->screen()->name, TheHouseholdsTabs::cases()),
        ...array_keys(REACHED_FROM_THEIR_SCREEN),
        new TheStacksSettingsInTheMenu()->screen()->name,
    ];
    $stranded = [];

    foreach (AStacksScreen::cases() as $screen) {
        $drawnUnderATab = WhereTheTabsAreDrawn::owning($served[$screen->name] ?? '') instanceof TheHouseholdsTabs;

        if ($screen->isAboutOneThing() || $drawnUnderATab || in_array($screen->name, $covered, strict: true)) {
            continue;
        }

        $stranded[] = $screen->name;
    }

    return $stranded;
}

// F18 — every screen of one stack is an operator's tab, a menu item, a member's tab or a screen that tab draws over itself, or a step of another screen named with where it begins
it('every screen of one stack is a tab, a menu item, or a step named with where it begins', function (): void {
    $stranded = theScreensStrandedWhenServing(theClassUnderEachScreen());

    expect($stranded)->toBe([], sprintf(
        "These screens of one stack are in no tab, no menu item and no list of steps:\n  %s\n\n"
        . "Add a TheMenu case that opens it, draw it under a member's tab, or name it in REACHED_FROM_THEIR_SCREEN with where it begins.\n",
        implode("\n  ", $stranded),
    ));
});

it('reads the screen a member\'s tab draws over itself as that tab\'s', function (): void {
    $served = theClassUnderEachScreen();

    expect($served['Guide'] ?? '')->toBe(WhatIsOnLiveTv::class)
        ->and(theScreensStrandedWhenServing($served))->not->toContain('Guide');
});

it('still strands a screen under no tab and in no list of steps', function (): void {
    expect(theScreensStrandedWhenServing([...theClassUnderEachScreen(), 'Guide' => YourStacks::class]))->toBe(['Guide']);
});

it('names only screens a stack has as steps, and does not let the steps grow', function (): void {
    $names = array_map(static fn(AStacksScreen $screen): string => $screen->name, AStacksScreen::cases());

    expect(array_diff(array_keys(REACHED_FROM_THEIR_SCREEN), $names))->toBe([])
        ->and(count(REACHED_FROM_THEIR_SCREEN))->toBeLessThanOrEqual(5);
});
