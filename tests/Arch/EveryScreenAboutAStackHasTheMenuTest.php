<?php

declare(strict_types=1);

use Modules\Operator\Internal\Screens\FindsItsWayAround;
use Modules\Operator\Internal\Screens\SignIntoAStack;
use Tests\Support\Screens;

// F17 — every operator screen about a stack carries the side menu.
//
// The menu is how an operator reaches every screen that is not a tab, so a
// screen about a stack without it is a screen they can only leave by going
// back. A screen is about a stack when it answers which stack it is about,
// and it carries the menu by using the trait that hands NativePHP the drawer.
// A screen that goes without names itself below with why, and the list may
// shrink and may not grow.

/** The screens about a stack that go without the menu, and why each does. */
const WITHOUT_THE_MENU = [
    SignIntoAStack::class => 'signing in shows the stack switcher and nothing else of the bar, until the stack has let the operator in',
];

it('F17 — every operator screen about a stack carries the menu, or is named with why it does not', function (): void {
    $wrong = [];
    $read = 0;

    foreach (Screens::all() as $screen) {
        if (! str_starts_with($screen->getName(), 'Modules\\Operator\\') || ! $screen->hasMethod('stack')) {
            continue;
        }

        $read++;
        $carries = in_array(FindsItsWayAround::class, $screen->getTraitNames(), strict: true);
        $excused = array_key_exists($screen->getName(), WITHOUT_THE_MENU);

        if ($carries === $excused) {
            $wrong[] = $screen->getName();
        }
    }

    expect($read)->toBeGreaterThan(0, 'no operator screen answers which stack it is about, so this read nothing')
        ->and($wrong)->toBe([], sprintf(
            "These screens about a stack carry no menu and are not named as going without, or are named and carry one anyway:\n  %s\n\n"
            . "Use `FindsItsWayAround` in the screen, or name it in WITHOUT_THE_MENU with why.\n",
            implode("\n  ", $wrong),
        ));
});

it('does not let the screens without the menu grow', function (): void {
    expect(count(WITHOUT_THE_MENU))->toBeLessThanOrEqual(1);
});
