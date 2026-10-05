<?php

declare(strict_types=1);

use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\Typeface;
use Modules\Design\Api\TypeSize;
use Modules\Design\Api\WhoseTheme;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('sets a stamp in the figures face at the caption size, in the faintest text', function (): void {
    $stamp = WhatMarkupDraws::drawn('<x-operator::stamp>0.16.0</x-operator::stamp>');

    expect(data_get($stamp, 'props.text'))->toBe('0.16.0')
        ->and(data_get($stamp, 'props.font_name'))->toBe(Typeface::Figures->value)
        ->and(data_get($stamp, 'props.font_size'))->toEqual(TypeSize::Caption->points())
        ->and(data_get($stamp, 'props.color'))->toBe(ThemeToken::Faint->in(WhoseTheme::Member))
        ->and(data_get($stamp, 'props.a11y_label'))->toBeNull();
});


it('reads a stamp aloud in full where what it shows is a shortening', function (): void {
    $stamp = WhatMarkupDraws::drawn('<x-operator::stamp answers-to="2026-10-05T21:39:01Z">21:39</x-operator::stamp>');

    expect(data_get($stamp, 'props.a11y_label'))->toBe('2026-10-05T21:39:01Z')
        ->and(data_get($stamp, 'props.text'))->toBe('21:39');
});
