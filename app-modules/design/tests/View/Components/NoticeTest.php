<?php

declare(strict_types=1);

use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhoseTheme;
use Modules\Design\View\Tone;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('draws a notice raised off the ground, its tone\'s glyph beside what it holds', function (): void {
    $markup = '<x-design::notice tone="trouble"><x-design::strong>Unreachable</x-design::strong></x-design::notice>';
    $drawn = WhatMarkupDraws::drawn($markup);

    expect(WhatMarkupDraws::outline($markup))
        ->toBe('row{"width":"fill","padding":16,"gap":12}[icon[][], column{"gap":4,"flex_grow":1,"flex_shrink":1}[Unreachable]]')
        ->and(data_get($drawn, 'style.bg_color'))->toBe(ThemeToken::Raised->in(WhoseTheme::Member))
        ->and(data_get($drawn, 'props.dark_bg_color'))->toBeNull()
        ->and(data_get($drawn, 'children.0.props.name'))->toBe(Tone::Trouble->glyph())
        ->and(data_get($drawn, 'children.0.props.color'))->toBe(ThemeToken::Text->in(WhoseTheme::Member))
        ->and(data_get($drawn, 'children.0.props.dark_color'))->toBeNull();
});

it('draws a notice as needing attention unless told otherwise', function (): void {
    expect(data_get(WhatMarkupDraws::drawn('<x-design::notice><x-design::body>A</x-design::body></x-design::notice>'), 'children.0.props.name'))
        ->toBe(Tone::Attention->glyph());
});
