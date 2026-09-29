<?php

declare(strict_types=1);

use Illuminate\View\ViewException;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('draws a row that goes somewhere with a chevron, and the name a reader hears', function (): void {
    $row = WhatMarkupDraws::drawn('<x-design::row headline="Open" supporting="Sonarr" goes="\'/s\'" answers-to="Open Sonarr" />');

    expect(data_get($row, 'type'))->toBe('list_item')
        ->and(data_get($row, 'props.headline'))->toBe('Open')
        ->and(data_get($row, 'props.supporting'))->toBe('Sonarr')
        ->and(data_get($row, 'props.trailing_icon'))->toBe('chevron_right')
        ->and(data_get($row, 'props.a11y_label'))->toBe('Open Sonarr')
        ->and(data_get($row, 'on_press'))->toBeInt();
});

it('draws a row that does something with a chevron, named by its headline', function (): void {
    $row = WhatMarkupDraws::drawn('<x-design::row headline="Check again" tap="again()" />');

    expect(data_get($row, 'props.trailing_icon'))->toBe('chevron_right')
        ->and(data_get($row, 'props.a11y_label'))->toBe('Check again')
        ->and(data_get($row, 'props'))->not->toHaveKey('supporting')
        ->and(data_get($row, 'on_press'))->toBeInt();
});

it('draws a row that does nothing with its value at its end and no chevron', function (): void {
    $row = WhatMarkupDraws::drawn('<x-design::row headline="Fingerprint" supporting="SHA-256" trailing="5adc" />');

    expect(data_get($row, 'props.trailing_value'))->toBe('5adc')
        ->and(data_get($row, 'props'))->not->toHaveKey('trailing_icon')
        ->and(data_get($row, 'props'))->not->toHaveKey('a11y_label')
        ->and($row)->not->toHaveKey('on_press');
});

it('draws a row with neither a note nor a value as its headline alone', function (): void {
    $row = WhatMarkupDraws::drawn('<x-design::row headline="Fingerprint" />');

    expect(data_get($row, 'props'))->toBe(['headline' => 'Fingerprint']);
});

it('starts a row that stands for something with a state with that state\'s glyph', function (): void {
    $row = WhatMarkupDraws::drawn('<x-design::row headline="Sonarr" tone="trouble" goes="\'/s\'" />');

    expect(data_get($row, 'props.leading_icon'))->toBe('error')
        ->and(data_get($row, 'props.leading_type'))->toBe('icon');
});

it('refuses a tone it has no glyph for', function (): void {
    WhatMarkupDraws::drawn('<x-design::row headline="Sonarr" tone="grand" />');
})->throws(ViewException::class);
