<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\TheTheme;
use Modules\Design\Api\Radius;
use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\Typeface;
use Modules\Design\Api\TypeSize;
use Modules\Design\Api\WhichThemeIsOnTheGlass;
use Modules\Design\Api\WhoseTheme;
use Native\Mobile\UI\Theme as WhatTheWidgetsPaintWith;

// Runs the composition root rather than reading it, so its mutants are judged
// here: see `scripts/mutation.php`.
pest()->group('holds:bootstrap/Composition');

// The widgets (filled buttons, list rows, fields, the top and bottom bars) take
// their colours from mobile-ui's theme store and honour no per-instance colour.
// So what a booted application holds in that store is what they paint with.

it('fills a primary control with the accent pair, whatever the phone is set to', function (): void {
    expect(config('native-ui.theme.light.primary'))->toBe(ThemeToken::Accent->in(WhoseTheme::Member))
        ->and(config('native-ui.theme.light.on-primary'))->toBe(ThemeToken::OnAccent->in(WhoseTheme::Member))
        ->and(config('native-ui.theme.dark.primary'))->toBe(ThemeToken::Accent->in(WhoseTheme::Member))
        ->and(config('native-ui.theme.dark.on-primary'))->toBe(ThemeToken::OnAccent->in(WhoseTheme::Member));
});

it('paints the widgets in the member\'s theme once booted, the same in light and in dark', function (): void {
    foreach (TheTheme::WIDGET_ROLES as $key => $role) {
        expect(config(sprintf('native-ui.theme.light.%s', $key)))->toBe($role->in(WhoseTheme::Member), $key)
            ->and(config(sprintf('native-ui.theme.dark.%s', $key)))->toBe($role->in(WhoseTheme::Member), $key);
    }

    expect(TheTheme::WIDGET_ROLES['background'])->toBe(ThemeToken::Surface)
        ->and(TheTheme::WIDGET_ROLES['on-background'])->toBe(ThemeToken::Text)
        ->and(TheTheme::WIDGET_ROLES['surface-variant'])->toBe(ThemeToken::Raised)
        ->and(TheTheme::WIDGET_ROLES['on-surface-variant'])->toBe(ThemeToken::Muted)
        ->and(TheTheme::WIDGET_ROLES['secondary'])->toBe(ThemeToken::Line)
        ->and(TheTheme::WIDGET_ROLES['on-secondary'])->toBe(ThemeToken::Text)
        ->and(TheTheme::WIDGET_ROLES['outline'])->toBe(ThemeToken::Line)
        ->and(TheTheme::WIDGET_ROLES)->not->toHaveKeys(['destructive', 'success', 'accent']);
});

it('leaves the widget colours it does not assert as the package set them', function (): void {
    // `merge()` rather than `load()`: the keys the design module maps are
    // replaced, and the rest of the package's palette stays.
    expect(config('native-ui.theme.light.destructive'))->not->toBeNull()
        ->and(config('native-ui.theme.light.success'))->not->toBeNull();
});

it('draws every widget, and any text naming no face, in the interface face, in either theme', function (WhoseTheme $theme): void {
    // The face rides the same payload as the colours: `default` is the alias
    // the renderers fall back to, and `font-family` what the platform chrome
    // takes it as.
    app(WhichThemeIsOnTheGlass::class)->paint($theme);

    expect(WhatTheWidgetsPaintWith::get('fonts'))->toBe(['default' => Typeface::Interface->value])
        ->and(WhatTheWidgetsPaintWith::get('font-family'))->toBe(Typeface::Interface->value);
})->with(WhoseTheme::cases());

it('rounds the widgets by the brand\'s radii, the pill kept for the full radius', function (): void {
    foreach (TheTheme::WIDGET_RADII as $key => $radius) {
        expect(WhatTheWidgetsPaintWith::get($key))->toBe($radius->points(), $key);
    }

    expect(TheTheme::WIDGET_RADII)->toBe([
        'radius-sm' => Radius::Small,
        'radius-md' => Radius::Medium,
        'radius-lg' => Radius::Medium,
        'radius-full' => Radius::Pill,
    ]);
});

it('sets the widgets\' text at the brand\'s sizes', function (): void {
    foreach (TheTheme::WIDGET_SIZES as $key => $size) {
        expect(WhatTheWidgetsPaintWith::get($key))->toBe($size->points(), $key);
    }

    expect(TheTheme::WIDGET_SIZES)->toBe([
        'font-sm' => TypeSize::Caption,
        'font-md' => TypeSize::Body,
        'font-lg' => TypeSize::Body,
        'font-xl' => TypeSize::DisplayM,
    ]);
});
