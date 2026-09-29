<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\TheTheme;
use Modules\Design\Api\ThemeToken;

// Runs the composition root rather than reading it, so its mutants are judged
// here: see `scripts/mutation.php`.
pest()->group('holds:bootstrap/Composition');

// The widgets (filled buttons, list rows, fields, the top and bottom bars) take
// their colours from mobile-ui's theme store and honour no per-instance colour.
// So what a booted application holds in that store is what they paint with.

it('fills a primary control with the accent pair in both modes', function (): void {
    expect(config('native-ui.theme.light.primary'))->toBe(ThemeToken::Accent->light())
        ->and(config('native-ui.theme.light.on-primary'))->toBe(ThemeToken::OnAccent->light())
        ->and(config('native-ui.theme.dark.primary'))->toBe(ThemeToken::Accent->dark())
        ->and(config('native-ui.theme.dark.on-primary'))->toBe(ThemeToken::OnAccent->dark());
});

it('paints the widgets\' grounds and text with paper and ink, and the ink theme in the dark', function (): void {
    foreach (TheTheme::WIDGET_ROLES as $key => $role) {
        expect(config(sprintf('native-ui.theme.light.%s', $key)))->toBe($role->light(), $key)
            ->and(config(sprintf('native-ui.theme.dark.%s', $key)))->toBe($role->dark(), $key);
    }

    expect(TheTheme::WIDGET_ROLES['background'])->toBe(ThemeToken::Surface)
        ->and(TheTheme::WIDGET_ROLES['on-background'])->toBe(ThemeToken::Text)
        ->and(TheTheme::WIDGET_ROLES['surface-variant'])->toBe(ThemeToken::Raised)
        ->and(TheTheme::WIDGET_ROLES['on-surface-variant'])->toBe(ThemeToken::Muted)
        ->and(TheTheme::WIDGET_ROLES['secondary'])->toBe(ThemeToken::Text)
        ->and(TheTheme::WIDGET_ROLES['outline'])->toBe(ThemeToken::Line)
        ->and(TheTheme::WIDGET_ROLES)->not->toHaveKeys(['destructive', 'success', 'accent']);
});

it('leaves the widget colours it does not assert as the package set them', function (): void {
    // `merge()` rather than `load()`: the keys the design module maps are
    // replaced, and the rest of the package's palette stays.
    expect(config('native-ui.theme.light.destructive'))->not->toBeNull()
        ->and(config('native-ui.theme.light.success'))->not->toBeNull();
});
