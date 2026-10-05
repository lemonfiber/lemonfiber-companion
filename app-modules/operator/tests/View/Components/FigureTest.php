<?php

declare(strict_types=1);

use Modules\Design\Api\Typeface;
use Modules\Design\Api\TypeSize;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('draws a figure under what it is, with its whole and unit beside it and its meaning under it', function (): void {
    $markup = '<x-operator::figure figure="412" out-of="/ 500" unit="GB" eyebrow="Used" caption="Of the media volume" />';

    expect(WhatMarkupDraws::outline($markup))
        ->toBe('column{"gap":8}[Used, row{"gap":8,"align_items":2}[412, / 500, GB], Of the media volume]');

    $figure = WhatMarkupDraws::drawn($markup);

    expect(data_get($figure, 'children.1.children.0.props.font_name'))->toBe(Typeface::FiguresMedium->value)
        ->and(data_get($figure, 'children.1.children.0.props.font_size'))->toEqual(TypeSize::DisplayM->points())
        ->and(data_get($figure, 'children.0.props.font_size'))->toEqual(TypeSize::Eyebrow->points())
        ->and(data_get($figure, 'children.2.props.font_size'))->toEqual(TypeSize::Caption->points());
});


it('says a figure nobody measured in words, with neither unit nor whole beside them', function (): void {
    $markup = '<x-operator::figure absent="Not measured yet" out-of="/ 500" unit="GB" />';

    expect(WhatMarkupDraws::outline($markup))->toBe('column{"gap":8}[Not measured yet]');
});
