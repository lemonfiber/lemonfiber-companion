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

it('draws where something stands as its glyph beside its words and its note', function (): void {
    $markup = '<x-design::standing said="Running" tone="fine" note="A minute ago" />';

    expect(WhatMarkupDraws::outline($markup))
        ->toBe('row{"width":"fill","gap":12,"align_items":1}[icon[][], column{"gap":4,"flex_grow":1,"flex_shrink":1}[Running, A minute ago]]')
        ->and(data_get(WhatMarkupDraws::drawn($markup), 'children.0.props.name'))->toBe(Tone::Fine->glyph());
});

it('draws where something stands without a note when it has none', function (): void {
    expect(WhatMarkupDraws::outline('<x-design::standing said="Running" tone="working" />'))
        ->toBe('row{"width":"fill","gap":12,"align_items":1}[icon[][], column{"gap":4,"flex_grow":1,"flex_shrink":1}[Running]]');
});

it('says where something stands in a word under its words, and to a reader of its glyph', function (): void {
    $markup = '<x-design::standing said="The disk is full" tone="trouble" note="Updated an hour ago" word="Broken" />';

    expect(WhatMarkupDraws::outline($markup))
        ->toBe('row{"width":"fill","gap":12,"align_items":1}[icon[][], column{"gap":4,"flex_grow":1,"flex_shrink":1}[The disk is full, Broken, Updated an hour ago]]')
        ->and(data_get(WhatMarkupDraws::drawn($markup), 'children.0.props.a11y_label'))->toBe('Broken');
});

it('gives the glyph no reader label where there is no word', function (): void {
    expect(data_get(WhatMarkupDraws::drawn('<x-design::standing said="Running" tone="fine" />'), 'children.0.props.a11y_label'))->toBeNull();
});

it('paints the glyph in the tone\'s severity in the operator\'s theme, and in text in the member\'s', function (): void {
    $markup = '<x-design::standing said="Sonarr" tone="fine" word="Running" />';

    expect(data_get(WhatMarkupDraws::drawnIn(WhoseTheme::Operator, $markup), 'children.0.props.color'))->toBe(ThemeToken::Ok->in(WhoseTheme::Operator))
        ->and(data_get(WhatMarkupDraws::drawnIn(WhoseTheme::Member, $markup), 'children.0.props.color'))->toBe(ThemeToken::Text->in(WhoseTheme::Member));
});
