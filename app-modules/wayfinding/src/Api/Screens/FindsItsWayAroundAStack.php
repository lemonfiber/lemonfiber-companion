<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api\Screens;

use Modules\News\Api\HowMuchIsNew;
use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Modules\Wayfinding\Api\WhoTheMenuIsFor;
use Modules\Wayfinding\Internal\ThePhonesSettingsInTheMenu;
use Modules\Wayfinding\Internal\TheRowsOfTheMenu;
use Modules\Wayfinding\Internal\TheStacksSettingsInTheMenu;
use Modules\Wayfinding\Internal\TheWhatsNewInTheMenu;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\UI\Builders\Drawer;

use function view;

/**
 * The way around, on every operator's screen about a stack.
 *
 * The stack's name in the top bar, and the menu's first row, open the list of
 * stacks. NativePHP asks a screen for `drawerOverride()` and draws what it
 * answers beside the screen, rendered against the screen, so a row's
 * navigation is the screen's own. Which rows the menu draws follows whose
 * session this phone holds for the stack.
 *
 * **The way back is the router's to say.** A screen opened on top of another
 * has the platform's back button, which the menu control sits beside, and
 * whether one lies beneath it is what the router holds, not what the screen
 * is: the screen a stack opens on, signing in included, can be the bottom of
 * the stack or be pushed over another. A tab is never opened on top of
 * anything, whatever the router holds beneath it, because switching tabs puts
 * one in the place of another.
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
            'whatsNew' => new TheWhatsNewInTheMenu($this->howMuchIsNewHere()),
            'stackSettings' => new TheStacksSettingsInTheMenu(),
            'appSettings' => new ThePhonesSettingsInTheMenu(),
        ]))
            ->label($this->around->theMenuIsCalled())
            ->besideBack(besideBack: $this->opensOnTopOfAnother())
            ->modal();
    }

    /** Open What's new on this stack, which the operator can widen to every stack from there. */
    public function openWhatsNew(): void
    {
        $this->navigate(new TheWhatsNewInTheMenu($this->howMuchIsNewHere())->goes(), [AScreenWithoutAStack::WHATS_NEW_SHOWS => $this->stack()->id()->stored()]);
    }

    /** How much is new on this stack, which the menu counts beside What's new. */
    abstract protected function howMuchIsNewHere(): HowMuchIsNew;

    /** Whether this screen is opened on top of another, and so has a back button. */
    protected function opensOnTopOfAnother(): bool
    {
        return ! $this->isDrawnAsATab() && $this->nativeRouter?->isRootScreen() === false;
    }

    /** Whether this screen is drawn as a tab, the root of what the phone draws for it. */
    abstract protected function isDrawnAsATab(): bool;
}
