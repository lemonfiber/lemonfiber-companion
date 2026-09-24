<?php

declare(strict_types=1);

use Modules\Design\Api\ThemeToken;

// Runs the composition root rather than reading it, so its mutants are judged
// here: see `scripts/mutation.php`.
pest()->group('holds:bootstrap/Composition');

// One brand colour maps to the accent role, leaving the rest of the
// palette to the platform. What makes that true of a running app is the widget
// theme, not a class: a filled button takes `primary` from it and honours no
// per-instance colour, so a screen painting its own buttons is a screen whose
// design is silently dropped.
//
// Asserted against the token rather than against a hex, so this cannot become
// the second place the brand is written down — which is the failure it exists
// to prevent, not one it is allowed to commit.

it('DES-R24 — the accent role is the brand accent', function (): void {
    expect(config('native-ui.theme.light.primary'))->toBe(ThemeToken::Accent->hex())
        ->and(config('native-ui.theme.light.on-primary'))->toBe(ThemeToken::OnAccent->hex());
});

it('DES-R24 — a reader in dark mode gets the same measured pair', function (): void {
    // There is deliberately no dark companion: ink on lemon measures 10.9:1
    // whichever way a reader has their phone set, so a second palette has
    // nothing to improve and would be a colour nobody chose.
    expect(config('native-ui.theme.dark.primary'))->toBe(ThemeToken::Accent->hex())
        ->and(config('native-ui.theme.dark.on-primary'))->toBe(ThemeToken::OnAccent->hex());
});

it('DES-R24 — the platform keeps the tokens this surface does not assert', function (): void {
    // The counterfactual for `merge()` rather than `load()`. Overriding two
    // keys must leave the rest of the package's palette standing; replacing the
    // block would repaint the app, which is the thing refused.
    expect(config('native-ui.theme.light.surface'))->not->toBeNull()
        ->and(config('native-ui.theme.light.secondary'))->not->toBeNull();
});
