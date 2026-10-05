<?php

declare(strict_types=1);

use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhoseTheme;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('draws a section as its label over a card holding its rows', function (): void {
    $markup = '<x-design::section label="This machine"><x-design::row headline="Storage" goes="\'/s\'" /></x-design::section>';
    $drawn = WhatMarkupDraws::drawn($markup);

    expect(WhatMarkupDraws::outline($markup))
        ->toBe('column{"width":"fill","gap":8}[This machine, column{"width":"fill","padding":[4,0,4,0]}[list_item[][]]]')
        ->and(data_get($drawn, 'children.1.style.bg_color'))->toBe(ThemeToken::Surface->in(WhoseTheme::Member))
        ->and(data_get($drawn, 'children.1.style.border_color'))->toBe(ThemeToken::Line->in(WhoseTheme::Member))
        ->and(data_get($drawn, 'children.1.props.dark_border_color'))->toBeNull()
        ->and(data_get($drawn, 'children.1.style.border_radius'))->toBe(16.0);
});

it('draws a section with no label as the card alone', function (): void {
    expect(WhatMarkupDraws::outline('<x-design::section><x-design::row headline="Storage" /></x-design::section>'))
        ->toBe('column{"width":"fill","gap":8}[column{"width":"fill","padding":[4,0,4,0]}[list_item[][]]]');
});
