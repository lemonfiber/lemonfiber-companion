<?php

declare(strict_types=1);

use Modules\Design\Api\ThemeToken;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('draws the dark runs of each row on the accent, each moved from the middle to its place inside a margin', function (): void {
    $markup = '<x-design::scannable :rows="$rows" missing="No code" />';
    $data = ['rows' => [[true, true, false], [false, true, true], [false, false, false]]];
    $drawn = WhatMarkupDraws::drawn($markup, $data);

    expect(data_get($drawn, 'style.bg_color'))->toBe(ThemeToken::Accent->light())
        ->and(data_get($drawn, 'layout.width'))->toEqual((3 + 8) * 4)
        ->and(data_get($drawn, 'children'))->toHaveCount(2)
        ->and(data_get($drawn, 'children.0.style.bg_color'))->toBe(ThemeToken::OnAccent->light())
        ->and(data_get($drawn, 'children.0.layout.width'))->toEqual(8)
        ->and(data_get($drawn, 'children.0.layout.height'))->toEqual(4)
        ->and(data_get($drawn, 'children.0.props.translate-x'))->toEqual(16 - (44 - 8) / 2)
        ->and(data_get($drawn, 'children.0.props.translate-y'))->toEqual(16 - (44 - 4) / 2)
        ->and(data_get($drawn, 'children.1.props.translate-x'))->toEqual(20 - (44 - 8) / 2)
        ->and(data_get($drawn, 'children.1.props.translate-y'))->toEqual(20 - (44 - 4) / 2)
        ->and(WhatMarkupDraws::outline($markup, $data))->not->toContain('No code');
});

it('draws a code of fifty squares a side in fewer elements than a square apiece would take', function (): void {
    $rows = [];

    for ($y = 0; $y < 53; $y++) {
        $row = [];

        for ($x = 0; $x < 53; $x++) {
            $row[] = ($x * 7 + $y * 3) % 5 < 2;
        }

        $rows[] = $row;
    }

    $drawn = WhatMarkupDraws::drawn('<x-design::scannable :rows="$rows" missing="No code" />', ['rows' => $rows]);
    $children = data_get($drawn, 'children');

    expect(is_array($children) ? count($children) : 0)->toBeLessThan(53 * 53 / 2);
});

it('says the words it is given where there is no code, rather than drawing an empty square', function (array $rows): void {
    expect(WhatMarkupDraws::outline('<x-design::scannable :rows="$rows" missing="No code" />', ['rows' => $rows]))->toContain('No code');
})->with([
    'no rows' => [[]],
    'a row with nothing in it' => [[[]]],
    'no dark square' => [[[false, false], [false, false]]],
]);
