<?php

declare(strict_types=1);

use Modules\Design\View\Tone;

it('gives every tone a glyph of its own on both platforms', function (): void {
    $android = array_map(static fn(Tone $tone): string => $tone->glyph(), Tone::cases());
    $ios = array_map(static fn(Tone $tone): string => $tone->iosGlyph(), Tone::cases());

    expect($android)->toBe(['check_circle', 'warning', 'error', 'help', 'schedule'])
        ->and($ios)->toBe([
            'checkmark.circle.fill',
            'exclamationmark.triangle.fill',
            'exclamationmark.octagon.fill',
            'questionmark.circle.fill',
            'clock.fill',
        ]);
});
