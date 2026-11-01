<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

/**
 * Each Android drawable made from a brand file, with the file, the cell size and the margin it is drawn at.
 *
 * Paths are from the bridge's `resources` directory, where the plugin's
 * manifest copies each drawable into the Android project from.
 */
enum WhatAndroidDrawsOfTheBrand: string
{
    /** The notification's small icon, the tray silhouette. */
    case TheNotificationIcon = 'android/res/drawable/lemonfiber_notification.xml';

    /** The launcher icon's foreground, the lemon. */
    case TheLauncherForeground = 'android/res/drawable/lemonfiber_launcher_foreground.xml';

    /** The launch mark, the lemon. */
    case TheLaunchMark = 'android/res/drawable/lemonfiber_launch_mark.xml';

    /** A status bar icon's side, in dp. */
    private const float A_STATUS_BAR_ICON = 24.0;

    /** An adaptive icon's side, in dp. */
    private const float AN_ADAPTIVE_ICON = 108.0;

    /** The launch mark's side, in dp. */
    private const float THE_LAUNCH_MARK = 96.0;

    /** A launcher foreground's cell, in dp: 32 cells make 64dp, inside the 66dp an adaptive icon keeps whole. */
    private const float A_LAUNCHER_CELL = 2.0;

    /** The brand file it is drawn from. */
    public function source(): string
    {
        return match ($this) {
            self::TheNotificationIcon => 'brand/tray-knockout.svg',
            self::TheLauncherForeground, self::TheLaunchMark => 'brand/mark-primary.svg',
        };
    }

    /** How wide the drawable is, in dp. */
    public function sideDp(): float
    {
        return match ($this) {
            self::TheNotificationIcon => self::A_STATUS_BAR_ICON,
            self::TheLauncherForeground => self::AN_ADAPTIVE_ICON,
            self::TheLaunchMark => self::THE_LAUNCH_MARK,
        };
    }

    /** How wide a cell is, in dp: the side across the drawing, but for the launcher foreground, which sits inside the side. */
    public function dpPerCell(ACellDrawing $drawing): float
    {
        return $this === self::TheLauncherForeground ? self::A_LAUNCHER_CELL : $this->sideDp() / $drawing->wide;
    }

    /** The drawable's XML, drawn from the cells of its brand file. */
    public function drawn(ACellDrawing $drawing): string
    {
        return new AnAndroidVector($drawing, $this->dpPerCell($drawing), $this->sideDp(), $this->source())->xml();
    }
}
