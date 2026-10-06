<?php

declare(strict_types=1);

use Illuminate\View\ViewException;
use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhoseTheme;
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

it('starts a row that names somewhere to go with its own glyph, beside its headline', function (): void {
    $row = WhatMarkupDraws::drawn('<x-design::row headline="Passwords" icon="key" ios-icon="key.horizontal" goes="\'/s\'" />');

    expect(data_get($row, 'props.leading_icon'))->toBe('key')
        ->and(data_get($row, 'props.headline'))->toBe('Passwords');
});

it('refuses a tone it has no glyph for', function (): void {
    WhatMarkupDraws::drawn('<x-design::row headline="Sonarr" tone="grand" />');
})->throws(ViewException::class);

it('paints a toned row\'s glyph in the tone\'s colour, and leaves a glyph of the row\'s own to the platform', function (): void {
    $toned = WhatMarkupDraws::drawnIn(WhoseTheme::Operator, '<x-design::row headline="Sonarr" tone="working" />');
    $own = WhatMarkupDraws::drawnIn(WhoseTheme::Operator, '<x-design::row headline="Health" icon="favorite" ios-icon="heart" tone="trouble" />');
    $plain = WhatMarkupDraws::drawnIn(WhoseTheme::Operator, '<x-design::row headline="Fingerprint" />');

    expect(data_get($toned, 'props.leading_icon_color'))->toBe(ThemeToken::Activity->in(WhoseTheme::Operator))
        ->and(data_get($own, 'props'))->not->toHaveKey('leading_icon_color')
        ->and(data_get($plain, 'props'))->not->toHaveKey('leading_icon_color');
});

it('hands the screen a row opens what it carries, besides the route, and nothing where it carries nothing', function (): void {
    $carrying = WhatMarkupDraws::roads('<x-design::row headline="App settings" goes="/settings" :carries="[\'spoken_to\' => \'a_member\']" />');
    $plain = WhatMarkupDraws::roads('<x-design::row headline="App settings" goes="/settings" />');

    expect($carrying)->toHaveCount(1)
        ->and(data_get($carrying, '0.uri'))->toBe('/settings')
        ->and(data_get($carrying, '0.data'))->toBe(['spoken_to' => 'a_member'])
        ->and(data_get($plain, '0.data'))->toBe([]);
});
