<?php

declare(strict_types=1);

use Modules\Design\Api\Radius;
use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhoseTheme;
use Modules\Design\View\Tone;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('draws a port as its tone\'s glyph on its tone\'s tile, in the operator\'s theme', function (string $tone, ThemeToken $ground, ThemeToken $edge, ThemeToken $glyph): void {
    $port = WhatMarkupDraws::drawnIn(WhoseTheme::Operator, sprintf('<x-operator::port tone="%s" />', $tone));

    expect(data_get($port, 'style.bg_color'))->toBe($ground->in(WhoseTheme::Operator))
        ->and(data_get($port, 'style.border_color'))->toBe($edge->in(WhoseTheme::Operator))
        ->and(data_get($port, 'style.border_radius'))->toEqual(Radius::Medium->points())
        ->and(data_get($port, 'layout.width'))->toEqual(40)
        ->and(data_get($port, 'children.0.props.name'))->toBe(Tone::from($tone)->glyph())
        ->and(data_get($port, 'children.0.props.color'))->toBe($glyph->in(WhoseTheme::Operator))
        ->and(data_get($port, 'children.0.props.a11y_label'))->toBeNull();
})->with([
    'as it should be' => ['fine', ThemeToken::Raised, ThemeToken::Line, ThemeToken::Ok],
    'under way' => ['working', ThemeToken::Raised, ThemeToken::Line, ThemeToken::Activity],
    'wanting a look' => ['attention', ThemeToken::WarnTint, ThemeToken::Warn, ThemeToken::Warn],
    'broken' => ['trouble', ThemeToken::Alarm, ThemeToken::Alarm, ThemeToken::OnAlarm],
]);


it('reads a port aloud only where it is given words of its own', function (): void {
    expect(data_get(WhatMarkupDraws::drawn('<x-operator::port tone="trouble" label="Broken" />'), 'children.0.props.a11y_label'))->toBe('Broken');
});
