<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Operator\Internal\WhereTheTabsAreDrawn;
use Modules\Wayfinding\Api\Screens\FindsItsWayAroundAStack;
use Modules\Wayfinding\Api\TheTabs;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;
use Native\Mobile\Edge\NativeComponent;

/**
 * The way around, on an operator's screen about a stack, with its tabs.
 *
 * A tab's screen is the root of what the phone draws for it; every other
 * screen about a stack has a back button wherever the router holds a screen
 * beneath it. The bottom bar is drawn on a tab's screens only.
 *
 * @phpstan-require-extends NativeComponent
 */
trait FindsItsWayAround
{
    use FindsItsWayAroundAStack;

    /** The tab this screen is drawn under, which the bar marks, or none. */
    public function itsTab(): ?TheTabs
    {
        return WhereTheTabsAreDrawn::owning(self::class);
    }

    /** The bar is hidden on a screen no tab owns. */
    public function tabBarOptions(): ?TabBarOptions
    {
        return $this->itsTab() instanceof TheTabs ? null : TabBarOptions::make()->hidden();
    }

    protected function isDrawnAsATab(): bool
    {
        return WhereTheTabsAreDrawn::drawnBy(self::class);
    }
}
