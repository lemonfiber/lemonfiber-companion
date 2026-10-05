<?php

declare(strict_types=1);

use Modules\Wayfinding\Api\Screens\FindsItsWayAroundAStack;
use Tests\Support\Screens;

// F17 — every screen about a stack, in either surface, carries the side menu
// and the list of stacks.
//
// The menu is how somebody reaches every screen that is not where they landed,
// so a screen about a stack without it is a screen they can only leave by going
// back. The list of stacks is how they reach another stack, and signing in
// carries it too. A screen is about a stack when it answers which stack it is
// about, and it carries both by using the wayfinding trait that hands NativePHP
// the drawer, directly or through its surface's own. A screen that goes without
// names itself below with why, and the list may not grow.

/**
 * The surfaces whose screens about a stack are held to this, by namespace.
 *
 * @var list<string>
 */
const SURFACES_ABOUT_A_STACK = ['Modules\\Operator\\', 'Modules\\Household\\'];

/**
 * The screens about a stack that go without the menu, and why each does.
 *
 * @var array<class-string, string>
 */
const WITHOUT_THE_MENU = [];

/**
 * The screens about a stack in each surface, by surface.
 *
 * @return array<string, list<ReflectionClass<object>>>
 */
function theScreensAboutAStack(): array
{
    $found = array_fill_keys(SURFACES_ABOUT_A_STACK, []);

    foreach (Screens::all() as $screen) {
        foreach (SURFACES_ABOUT_A_STACK as $surface) {
            if (str_starts_with($screen->getName(), $surface) && $screen->hasMethod('stack')) {
                $found[$surface][] = $screen;
            }
        }
    }

    return $found;
}

// F17 — every screen about a stack, in either surface, carries the side menu and the list of stacks, and one that goes without the menu is named with why
it('every screen about a stack in either surface carries the menu, or is named with why it does not', function (): void {
    $wrong = [];

    foreach (theScreensAboutAStack() as $surface => $screens) {
        expect($screens)->not->toBeEmpty(sprintf('no screen under %s answers which stack it is about, so this read nothing there', $surface));

        foreach ($screens as $screen) {
            $carries = array_any(Screens::traitsOf($screen), static fn(ReflectionClass $trait): bool => $trait->getName() === FindsItsWayAroundAStack::class);
            $excused = array_key_exists($screen->getName(), WITHOUT_THE_MENU);

            if ($carries === $excused) {
                $wrong[] = $screen->getName();
            }
        }
    }

    expect($wrong)->toBe([], sprintf(
        "These screens about a stack carry no menu and are not named as going without, or are named and carry one anyway:\n  %s\n\n"
        . "Use the surface's way around in the screen (`FindsItsWayAround` for the operator, `FindsItsWayAroundTheHouse` for a member), or name it in WITHOUT_THE_MENU with why.\n",
        implode("\n  ", $wrong),
    ));
});

it('does not let the screens without the menu grow', function (): void {
    expect(WITHOUT_THE_MENU)->toBe([]);
});

// F17 — every screen about a stack, in either surface, carries the side menu and the list of stacks, and one that goes without the menu is named with why
it('every screen about a stack in either surface carries the list of stacks', function (): void {
    $without = [];

    foreach (theScreensAboutAStack() as $screens) {
        foreach ($screens as $screen) {
            if (! $screen->hasMethod('chooseAStack')) {
                $without[] = $screen->getName();
            }
        }
    }

    expect($without)->toBe([], sprintf(
        "These screens about a stack carry no list of stacks:\n  %s\n\n"
        . "Use the surface's way around in the screen, which carries it.\n",
        implode("\n  ", $without),
    ));
});
