<?php

declare(strict_types=1);

use Modules\Design\Api\ThemeToken;
use Modules\Design\View\Tone;

it('gives every tone a glyph of its own on both platforms', function (): void {
    $android = array_map(static fn(Tone $tone): string => $tone->glyph(), Tone::cases());
    $ios = array_map(static fn(Tone $tone): string => $tone->iosGlyph(), Tone::cases());

    expect($android)->toBe(['check_circle', 'warning', 'error', 'help', 'schedule', 'radio_button_unchecked'])
        ->and($ios)->toBe([
            'checkmark.circle.fill',
            'exclamationmark.triangle.fill',
            'exclamationmark.octagon.fill',
            'questionmark.circle.fill',
            'clock.fill',
            'circle',
        ]);
});

it('paints each tone\'s glyph in its severity, and the rest in the text roles', function (): void {
    expect(array_map(static fn(Tone $tone): ThemeToken => $tone->colour(), Tone::cases()))->toBe([
        ThemeToken::Ok,
        ThemeToken::Warn,
        ThemeToken::Alarm,
        ThemeToken::Muted,
        ThemeToken::Activity,
        ThemeToken::Faint,
    ]);
});

it('raises a notice on a tint only where something wants looking at or is broken', function (): void {
    expect(array_map(static fn(Tone $tone): ThemeToken => $tone->ground(), Tone::cases()))->toBe([
        ThemeToken::Raised,
        ThemeToken::WarnTint,
        ThemeToken::AlarmTint,
        ThemeToken::Raised,
        ThemeToken::Raised,
        ThemeToken::Raised,
    ]);
});
