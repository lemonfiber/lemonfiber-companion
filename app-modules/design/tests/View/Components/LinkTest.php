<?php

declare(strict_types=1);

use Modules\Design\Api\ThemeToken;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('draws a link as its words and a chevron on a target a thumb can find', function (string $markup): void {
    $link = WhatMarkupDraws::drawn($markup);

    expect(WhatMarkupDraws::outline($markup))
        ->toBe('pressable{"width":"fill","min_height":48,"flex_direction":1,"padding":[8,0,8,0],"gap":8,"align_items":1,"justify_content":3}[Read the log, icon[][]]')
        ->and(data_get($link, 'props.a11y_label'))->toBe('Read the log')
        ->and(data_get($link, 'children.1.props.color'))->toBe(ThemeToken::Muted->light())
        ->and(data_get($link, 'children.1.props.dark_color'))->toBe(ThemeToken::Muted->dark())
        ->and(data_get($link, 'on_press'))->toBeInt();
})->with([
    'going somewhere' => ['<x-design::link label="Read the log" goes="\'/logs\'" />'],
    'doing something' => ['<x-design::link label="Read the log" tap="read()" />'],
]);

it('names a link for what it acts on where its words alone would not', function (): void {
    expect(data_get(WhatMarkupDraws::drawn('<x-design::link label="Read the log" goes="\'/l\'" answers-to="Read what Sonarr said" />'), 'props.a11y_label'))
        ->toBe('Read what Sonarr said');
});
