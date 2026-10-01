<?php

declare(strict_types=1);

use Modules\Operator\Internal\TheMenu;
use Modules\Operator\Internal\TheTabs;
use Modules\Stacks\Api\AStacksScreen;

// F18 — every screen of one stack is a tab, a menu item, or a step of another.
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
    'Owed' => 'a member of the household\'s own screen, which the household surface draws',
    'Shelf' => 'a member of the household\'s own screen, which the household surface draws',
    'Versions' => 'Versions details what About says is running, and is reached from About',
];

it('F18 — every screen of one stack is a tab, a menu item, or a step named with where it begins', function (): void {
    $covered = [
        ...array_map(static fn(TheMenu $item): string => $item->screen()->name, TheMenu::cases()),
        ...array_map(static fn(TheTabs $tab): string => $tab->screen()->name, TheTabs::cases()),
        ...array_keys(REACHED_FROM_THEIR_SCREEN),
    ];
    $stranded = [];

    foreach (AStacksScreen::cases() as $screen) {
        if ($screen->alsoNeedsAService() || in_array($screen->name, $covered, strict: true)) {
            continue;
        }

        $stranded[] = $screen->name;
    }

    expect($stranded)->toBe([], sprintf(
        "These screens of one stack are in no tab, no menu item and no list of steps:\n  %s\n\n"
        . "Add a TheMenu case that opens it, or name it in REACHED_FROM_THEIR_SCREEN with where it begins.\n",
        implode("\n  ", $stranded),
    ));
});

it('names only screens a stack has as steps, and does not let the steps grow', function (): void {
    $names = array_map(static fn(AStacksScreen $screen): string => $screen->name, AStacksScreen::cases());

    expect(array_diff(array_keys(REACHED_FROM_THEIR_SCREEN), $names))->toBe([])
        ->and(count(REACHED_FROM_THEIR_SCREEN))->toBeLessThanOrEqual(7);
});
