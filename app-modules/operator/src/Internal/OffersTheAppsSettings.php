<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * The way to this app's page in the phone's settings, from a screen showing an obstacle put right there.
 *
 * A trait for {@see LetsGoOfARefusedSession}'s reason: it is the same one
 * action on every screen that draws an obstacle, and a control a template
 * draws can only call the screen it is drawn on. Which obstacles offer it is
 * not decided here but by {@see \Modules\Kernel\Api\Obstacle::isPutRightInTheAppsSettings()},
 * so every screen offers it for the same ones.
 *
 * The screen that uses it takes {@see \Modules\Kernel\Api\TheAppsSettings} as a protected
 * `$settings`. Protected because only this trait reads it, and an analyser that
 * does not follow a trait reads a private one as never used.
 */
trait OffersTheAppsSettings
{
    /**
     * Whether the phone would not open the page the last time it was asked.
     *
     * `public`, which is what `NativeComponent`'s property syncing needs to
     * reach, and what the template reads to say so beside the button.
     */
    public bool $theSettingsWouldNotOpen = false;

    /** Open this app's page in the phone's settings, and remember whether the phone would not. */
    public function openTheAppsSettings(): void
    {
        $this->theSettingsWouldNotOpen = $this->settings->open()->wouldNotOpen();
    }
}
