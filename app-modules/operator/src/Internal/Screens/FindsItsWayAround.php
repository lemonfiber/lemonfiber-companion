<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Operator\Internal\TheTabs;
use Modules\Operator\Internal\WhereInTheMenu;
use Native\Mobile\UI\Builders\Drawer;

use function view;

/**
 * The side menu, on every screen about a stack that uses this.
 *
 * NativePHP asks a screen for `drawerOverride()` and draws what it answers
 * beside the screen, rendered against the screen, so a row's navigation is
 * the screen's own. A tab's screen is the root of what the phone draws for
 * it; every other screen about a stack is opened on top of one and has a back
 * button, which the menu control sits beside.
 */
trait FindsItsWayAround
{
    public function drawerOverride(): Drawer
    {
        return Drawer::make(view('operator::the-menu', [
            'stack' => $this->stack(),
            'groups' => WhereInTheMenu::cases(),
        ]))
            ->label($this->around->theMenuIsCalled())
            ->besideBack(besideBack: ! TheTabs::drawnBy(self::class))
            ->modal();
    }
}
