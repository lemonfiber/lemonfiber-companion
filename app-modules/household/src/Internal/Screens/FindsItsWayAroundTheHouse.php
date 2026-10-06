<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Modules\Household\Internal\WhereTheHouseIs;
use Modules\Household\Internal\WhereTheTabsAreDrawn;
use Modules\Kernel\Api\Stack;
use Modules\Wayfinding\Api\TheHouseholdsTabs;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;
use Native\Mobile\Edge\NativeComponent;

/**
 * The way around, on a member's screen about a house: four tabs and no menu.
 *
 * A tab's screen carries the bar and has no back button, because switching
 * tabs puts one in the place of another. Every other member screen is opened
 * over a tab, hides the bar, and has the platform's way back wherever the
 * router holds a screen beneath it. There is no side menu and no list of
 * houses in the top bar: changing house is on Profile.
 *
 * **It reads the screen's own `$around`**, which answers the stack the route
 * names. That is the coupling, stated here because a trait cannot declare it.
 * The screen takes `$around` as protected where only this trait reads it,
 * because an analyser that does not follow a trait reads a private one as
 * never used.
 *
 * @phpstan-require-extends NativeComponent
 */
trait FindsItsWayAroundTheHouse
{
    /**
     * The house this screen is about.
     *
     * Read from the route on every frame rather than held, so there is one
     * answer to *which house* and it is the one the URI names.
     */
    public function stack(): Stack
    {
        return $this->around->stackOn($this);
    }

    /** Where this house's screens are, which the bar's tabs lead to. */
    public function goes(): WhereTheHouseIs
    {
        return WhereTheHouseIs::of($this->stack()->id());
    }

    /** The tab this screen is drawn under, which the bar marks, or none. */
    public function itsTab(): ?TheHouseholdsTabs
    {
        return WhereTheTabsAreDrawn::owning(self::class);
    }

    /** The bar is hidden on a screen no tab draws. */
    public function tabBarOptions(): ?TabBarOptions
    {
        return WhereTheTabsAreDrawn::drawnBy(self::class) ? null : TabBarOptions::make()->hidden();
    }

    /** Whether this screen is opened on top of another, and so has a back button. */
    public function hasAWayBack(): bool
    {
        return ! WhereTheTabsAreDrawn::drawnBy(self::class) && $this->nativeRouter?->isRootScreen() === false;
    }
}
