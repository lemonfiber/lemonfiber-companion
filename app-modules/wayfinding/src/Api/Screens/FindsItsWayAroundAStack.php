<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api\Screens;

use Modules\Wayfinding\Api\WhatIsNotHereYet;
use Modules\Wayfinding\Api\WhoTheMenuIsFor;
use Modules\Wayfinding\Internal\ThePhonesSettingsInTheMenu;
use Modules\Wayfinding\Internal\TheRowsOfTheMenu;
use Modules\Wayfinding\Internal\TheStacksSettingsInTheMenu;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\UI\Builders\Drawer;

use function view;

/**
 * The way around, on every screen about a stack in either surface.
 *
 * The stack's name in the top bar, and the menu's first row, open the list of
 * stacks. NativePHP asks a screen for `drawerOverride()` and draws what it
 * answers beside the screen, rendered against the screen, so a row's
 * navigation is the screen's own. Which rows the menu draws follows whose
 * session this phone holds for the stack. A screen opened on top of another
 * has a back button, which the menu control sits beside.
 *
 * @phpstan-require-extends NativeComponent
 */
trait FindsItsWayAroundAStack
{
    use ChoosesAStack;

    /**
     * Whose menu this screen draws, read once from the phone's keychain.
     *
     * Held because the menu is drawn with every frame, polls included, and the
     * keychain is across a process boundary. A screen that keeps a session
     * sets it back to unread.
     */
    public ?WhoTheMenuIsFor $menuIsFor = null;

    public function drawerOverride(): Drawer
    {
        return Drawer::make(view('wayfinding::the-menu', [
            'stack' => $this->stack(),
            'rows' => TheRowsOfTheMenu::for($this->menuIsFor ??= $this->around->whoTheMenuIsFor($this->stack())),
            'whatsNew' => WhatIsNotHereYet::WhatsNew,
            'stackSettings' => new TheStacksSettingsInTheMenu(),
            'appSettings' => new ThePhonesSettingsInTheMenu(),
        ]))
            ->label($this->around->theMenuIsCalled())
            ->besideBack(besideBack: $this->opensOnTopOfAnother())
            ->modal();
    }

    /** Whether this screen is opened on top of another, and so has a back button. */
    abstract protected function opensOnTopOfAnother(): bool;
}
