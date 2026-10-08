<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Kernel\Api\AnAction;
use Modules\Kernel\Api\Stack;
use Modules\Operator\Internal\HoldsItsStacksStream;
use Modules\Operator\Internal\ViewModels\AnOffer;
use Modules\Operator\Internal\WhereAStackIs;
use Modules\Operator\Internal\WhereTheTabsAreDrawn;
use Modules\Stacks\Api\AStacksScreen;
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
 * **Every operator's screen about a stack holds that stack's stream while it is
 * in front**, through {@see HoldsItsStacksStream}, so the bar marks a tab
 * holding something new on whichever screen draws it, and the list of stacks
 * the top bar's name opens does not ask that stack again.
 *
 * **It reads the screen's own `$around`**, which answers the stack the route
 * names. That is the coupling, stated here because a trait cannot declare it.
 * The screen takes `$around` as protected, because only its traits read it,
 * and an analyser that does not follow a trait reads a private one as never
 * used.
 *
 * @phpstan-require-extends NativeComponent
 */
trait FindsItsWayAround
{
    use FindsItsWayAroundAStack;
    use HoldsItsStacksStream;

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, so there is one
     * answer to *which machine* and it is the one the URI names. A held stack
     * is how a screen comes to be showing one machine's name while offering
     * another machine the password.
     *
     * Raises {@see \Modules\Kernel\Api\StackIsNotConfigured} where the
     * device holds no such stack, which is a route naming a stack that has been
     * forgotten: a launch-time fault rather than a screen state. A route
     * parameter that is not a string is refused as a route naming no stack.
     */
    public function stack(): Stack
    {
        return $this->around->stackOn($this);
    }

    /**
     * Where this machine's screens are.
     *
     * One accessor rather than one per destination: {@see WhereAStackIs} is the
     * only place that knows a stack's routes, and it is built from the stack
     * this screen is about, so none of them can lead to another machine's.
     */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    /**
     * What a button for this action says, asked of the stack before it is drawn.
     *
     * One question for every button on every screen about a stack, so none
     * works out for itself whether the stack is too old for it, or whether it
     * is this account's to ask for.
     */
    public function offered(AnAction $action): AnOffer
    {
        return AnOffer::of($this->around->offers($this->stack(), $action), $this->goes()->to(AStacksScreen::Updates));
    }

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
