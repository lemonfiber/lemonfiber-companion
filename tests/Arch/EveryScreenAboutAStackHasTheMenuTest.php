<?php

declare(strict_types=1);

use Modules\Household\Internal\Screens\FindsItsWayAroundTheHouse;
use Modules\Wayfinding\Api\Screens\FindsItsWayAroundAStack;
use Tests\Support\Screens;

// F17 — every operator screen about a stack carries the side menu and the list
// of stacks; every member screen about a stack carries the member's four tabs
// and no menu.
//
// The menu is how the operator reaches every screen that is not where they
// landed, so an operator's screen about a stack without it is a screen they can
// only leave by going back. The list of stacks is how they reach another
// stack, and signing in carries it too. A member finds their way by four tabs
// and changes house on Profile, so a member's screen carries neither the menu
// nor the list in its top bar. A screen is about a stack when it answers which
// stack it is about, and it carries its surface's way around by using that
// surface's trait. A screen that goes without names itself below with why, and
// the list may not grow.

/** The operator's screens, by namespace. */
const THE_OPERATORS_SCREENS = 'Modules\\Operator\\';

/** The member's screens, by namespace. */
const THE_MEMBERS_SCREENS = 'Modules\\Household\\';

/**
 * The screens about a stack that go without their surface's way around, and why each does.
 *
 * @var array<class-string, string>
 */
const WITHOUT_THE_WAY_AROUND = [];

/**
 * The screens about a stack in one surface.
 *
 * @return list<ReflectionClass<object>>
 */
function theScreensAboutAStackIn(string $surface): array
{
    $found = [];

    foreach (Screens::all() as $screen) {
        if (str_starts_with($screen->getName(), $surface) && $screen->hasMethod('stack')) {
            $found[] = $screen;
        }
    }

    return $found;
}

/**
 * Whether a screen uses one trait, directly or through another of its traits.
 *
 * @param ReflectionClass<object> $screen
 */
function usesTheWayAround(ReflectionClass $screen, string $trait): bool
{
    return array_any(Screens::traitsOf($screen), static fn(ReflectionClass $used): bool => $used->getName() === $trait);
}

// F17 — every operator screen about a stack carries the side menu and the list of stacks, and every member screen about a stack carries the member's four tabs and no menu; one that goes without is named with why
it('every operator screen about a stack carries the menu and the list of stacks, or is named with why it does not', function (): void {
    $screens = theScreensAboutAStackIn(THE_OPERATORS_SCREENS);
    $wrong = [];

    expect($screens)->not->toBeEmpty('no operator screen answers which stack it is about, so this read nothing');

    foreach ($screens as $screen) {
        $carries = usesTheWayAround($screen, FindsItsWayAroundAStack::class) && $screen->hasMethod('chooseAStack');

        if ($carries === array_key_exists($screen->getName(), WITHOUT_THE_WAY_AROUND)) {
            $wrong[] = $screen->getName();
        }
    }

    expect($wrong)->toBe([], sprintf(
        "These operator screens about a stack carry no menu or list of stacks and are not named as going without, or are named and carry them anyway:\n  %s\n\n"
        . "Use `FindsItsWayAround` in the screen, or name it in WITHOUT_THE_WAY_AROUND with why.\n",
        implode("\n  ", $wrong),
    ));
});

// F17 — every operator screen about a stack carries the side menu and the list of stacks, and every member screen about a stack carries the member's four tabs and no menu; one that goes without is named with why
it('every member screen about a stack carries the four tabs and neither the menu nor the list of stacks, or is named with why it does not', function (): void {
    $screens = theScreensAboutAStackIn(THE_MEMBERS_SCREENS);
    $wrong = [];

    expect($screens)->not->toBeEmpty('no member screen answers which stack it is about, so this read nothing');

    foreach ($screens as $screen) {
        $carries = usesTheWayAround($screen, FindsItsWayAroundTheHouse::class)
            && ! $screen->hasMethod('drawerOverride')
            && ! $screen->hasMethod('chooseAStack');

        if ($carries === array_key_exists($screen->getName(), WITHOUT_THE_WAY_AROUND)) {
            $wrong[] = $screen->getName();
        }
    }

    expect($wrong)->toBe([], sprintf(
        "These member screens about a stack do not carry the four tabs alone, and are not named as going without:\n  %s\n\n"
        . "Use `FindsItsWayAroundTheHouse` in the screen, and no menu or list of stacks, or name it in WITHOUT_THE_WAY_AROUND with why.\n",
        implode("\n  ", $wrong),
    ));
});

it('does not let the screens without the way around grow', function (): void {
    expect(WITHOUT_THE_WAY_AROUND)->toBe([]);
});
