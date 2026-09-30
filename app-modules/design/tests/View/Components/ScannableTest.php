<?php

declare(strict_types=1);

use Modules\Design\Api\ThemeToken;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('draws each row of the code as squares, dark on the accent', function (): void {
    $markup = '<x-design::scannable :rows="$rows" missing="No code" />';
    $drawn = WhatMarkupDraws::drawn($markup, ['rows' => [[true, false], [false, true]]]);
    $rows = data_get($drawn, 'children');

    expect($rows)->toHaveCount(2)
        ->and(data_get($drawn, 'children.0.children'))->toHaveCount(2)
        ->and(data_get($drawn, 'children.0.style.bg_color'))->toBe(ThemeToken::Accent->light())
        ->and(data_get($drawn, 'children.0.children.0.style.bg_color'))->toBe(ThemeToken::OnAccent->light())
        ->and(data_get($drawn, 'children.0.children.1.style.bg_color'))->toBe(ThemeToken::Accent->light())
        ->and(WhatMarkupDraws::outline($markup, ['rows' => [[true, false], [false, true]]]))->not->toContain('No code');
});

it('says the words it is given where there is no code, rather than drawing an empty square', function (): void {
    expect(WhatMarkupDraws::outline('<x-design::scannable :rows="[]" missing="No code" />'))->toContain('No code');
});

it('says the words it is given for a row with nothing in it', function (): void {
    expect(WhatMarkupDraws::outline('<x-design::scannable :rows="[[]]" missing="No code" />'))->toContain('No code');
});
