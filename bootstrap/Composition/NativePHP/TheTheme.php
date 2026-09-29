<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use Modules\Design\Api\Theme;
use Modules\Design\Api\ThemeToken;
use Native\Mobile\Edge\TailwindParser;
use Native\Mobile\UI\Theme as WhatTheWidgetsPaintWith;

/**
 * Whose palette the classes and the widgets paint with.
 *
 * `TailwindParser` holds one light and one dark resolver and keeps whichever
 * was set last, and `nativephp/mobile-ui` sets its own in its boot. Provider
 * discovery order would decide the palette, and it differs between a fresh
 * `composer install` and an incremental one. So this is a method the
 * composition root calls from `$this->app->booted()`, after every provider,
 * and a test can plant a foreign resolver and call it.
 */
final readonly class TheTheme
{
    /**
     * What each key of mobile-ui's theme store is painted with.
     *
     * The widgets (buttons, list rows, fields, the top bar and the bottom bar)
     * read their colours from that store rather than from classes. Every
     * neutral key is mapped, so no widget falls back to the package's own
     * palette. Material draws a selected tab's label in `secondary`, so it is
     * the text role; the tab's indicator and a tonal button are painted from
     * the outline (see `scripts/patch_nativephp.php`). `destructive`, `success` and the package's `accent` are not:
     * this surface asserts no colour for them, and uses no widget variant that
     * would.
     */
    public const array WIDGET_ROLES = [
        'primary' => ThemeToken::Accent,
        'on-primary' => ThemeToken::OnAccent,
        'secondary' => ThemeToken::Text,
        'on-secondary' => ThemeToken::Surface,
        'background' => ThemeToken::Surface,
        'on-background' => ThemeToken::Text,
        'surface' => ThemeToken::Surface,
        'on-surface' => ThemeToken::Text,
        'surface-variant' => ThemeToken::Raised,
        'on-surface-variant' => ThemeToken::Muted,
        'outline' => ThemeToken::Line,
        'outline-variant' => ThemeToken::Line,
    ];

    /**
     * Make this application's roles the ones classes and widgets resolve against.
     *
     * Setting a resolver replaces the one before it, which is what makes this
     * idempotent. The widget theme is merged rather than loaded: the keys the
     * design module maps are replaced, and the ones it does not assert stay.
     */
    public static function paint(): void
    {
        TailwindParser::setThemeResolver(Theme::resolver());
        TailwindParser::setThemeDarkResolver(Theme::darkResolver());

        $light = [];
        $dark = [];

        foreach (self::WIDGET_ROLES as $key => $role) {
            $light[$key] = $role->light();
            $dark[$key] = $role->dark();
        }

        WhatTheWidgetsPaintWith::merge(['light' => $light, 'dark' => $dark]);
    }
}
