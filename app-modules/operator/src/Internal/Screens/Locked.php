<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LockingAfter;
use Modules\Kernel\Api\DeviceAuth;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhenTheLockAsks;
use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;
use Override;

use function view;

/**
 * The lock, and nothing behind it.
 *
 * What the navigation stack builds in place of any screen while the device's
 * lock stands, and what goes over the screen on view when the device stands it
 * again. It draws one sentence and one button: no top bar, no menu, no tabs,
 * no stack names and nothing kept, because all of it is what the lock is for.
 * No top bar also means no back arrow and no edge swipe, and the device's back
 * button does nothing here.
 *
 * **Opening is always the device's answer.** A tap asks the device and waits
 * for it; a prompt the device raised by itself is heard as the lock moving,
 * and read here afresh. Either way the lock is read through the bridge, and
 * only an open lock goes on.
 *
 * **Going on is going back to where the lock came from.** Put over a screen,
 * it steps back to that screen, whose state was kept under it. Built in place
 * of one, it builds that one now.
 */
#[Lazy]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class Locked extends NativeComponent
{
    /** Whether the screen under this awaits the outcome of something it sent. */
    public const string AWAITS = 'awaits';

    public function __construct(private readonly DeviceAuth $device, private readonly LockingAfter $lockingAfter) {}

    /**
     * Ask the device, because the operator tapped.
     *
     * A tap is what the operator meant, so it asks even where the device would
     * not ask by itself.
     */
    public function tryToUnlock(): void
    {
        $this->device->unlock()->either(
            held: fn(): self => $this,
            open: fn(): self => $this->goOn(),
        );
    }

    /** The device says the lock moved: go on if it opened. */
    public function lockMoved(): void
    {
        $this->device->standing()->either(
            held: fn(): self => $this,
            open: fn(): self => $this->goOn(),
        );
    }

    /**
     * This frame is on the glass, so the device may stop covering it.
     *
     * The device also asks by itself, once per time its lock stood, unless the
     * screen under this was still waiting on something it sent: a prompt
     * raised over that is answered to be rid of it rather than meant.
     */
    public function drawn(): void
    {
        $this->device->drawn($this->data(self::AWAITS) === true ? WhenTheLockAsks::OnlyWhenTapped : WhenTheLockAsks::ByItself);
    }

    /** Come back to after a screen above this closed: go on if the lock opened meanwhile. */
    #[Override]
    public function onResume(): void
    {
        $this->lockMoved();
    }

    /** The device's back button leaves the lock where it is. */
    #[Override]
    public function onBackPressed(): void {}

    public function render(): View
    {
        return view('operator::locked');
    }

    /**
     * Back to the screen the lock went over, or on to the one it stood in for.
     *
     * The device is told how long the app may be away first: the lock is open,
     * so what the phone keeps may be read, and a device that has just started
     * knows only its own default.
     */
    private function goOn(): self
    {
        $this->lockingAfter->toldTheDevice();

        $here = $this->nativeRouter?->currentUri() ?? AScreenWithoutAStack::TheList->value;

        if ($here !== AScreenWithoutAStack::Locked->value) {
            return $this->replace($here);
        }

        return $this->nativeRouter?->isRootScreen() === false ? $this->back() : $this->replace(AScreenWithoutAStack::TheList->value);
    }
}
