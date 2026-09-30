<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Operator\Internal\TheTabs;
use Modules\Operator\Internal\WhatIsNotHereYet;
use Modules\Operator\Internal\WhereInTheMenu;
use Native\Mobile\Edge\Layouts\Builders\TabBarOptions;
use Native\Mobile\UI\Builders\Drawer;

use function view;

/**
 * The side menu, on every screen about a stack that uses this.
 *
 * NativePHP asks a screen for `drawerOverride()` and draws what it answers
 * beside the screen, rendered against the screen, so a row's navigation is
 * the screen's own. A tab's screen is the root of what the phone draws for
 * it; every other screen about a stack is opened on top of one and has a back
 * button, which the menu control sits beside. The bottom bar is drawn on a
 * tab's screens only. The stack's name in the top bar, and the menu's first
 * row, open the list of stacks.
 */
trait FindsItsWayAround
{
    use ChoosesAStack;

    /** The tab this screen is drawn under, which the bar marks, or none. */
    public function itsTab(): ?TheTabs
    {
        return TheTabs::owning(self::class);
    }

    /** The bar is hidden on a screen no tab owns. */
    public function tabBarOptions(): ?TabBarOptions
    {
        return $this->itsTab() instanceof TheTabs ? null : TabBarOptions::make()->hidden();
    }

    public function drawerOverride(): Drawer
    {
        return Drawer::make(view('operator::the-menu', [
            'stack' => $this->stack(),
            'whatsNew' => WhatIsNotHereYet::WhatsNew,
            'groups' => WhereInTheMenu::cases(),
            'settings' => [WhatIsNotHereYet::StackSettings, WhatIsNotHereYet::AppSettings],
        ]))
            ->label($this->around->theMenuIsCalled())
            ->besideBack(besideBack: ! TheTabs::drawnBy(self::class))
            ->modal();
    }
}
