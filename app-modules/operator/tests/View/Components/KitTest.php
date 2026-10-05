<?php

declare(strict_types=1);

use Modules\Design\Api\Radius;
use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\Typeface;
use Modules\Design\Api\TypeSize;
use Modules\Design\Api\WhoseTheme;
use Modules\Design\View\Tone;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('draws a rule as the platform\'s divider in the line role', function (): void {
    $rule = WhatMarkupDraws::drawn('<x-operator::rule />');

    expect(data_get($rule, 'type'))->toBe('divider')
        ->and(data_get($rule, 'style.border_color'))->toBe(ThemeToken::Line->in(WhoseTheme::Member));
});

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

it('draws a port row as one target ending in a chevron, its port leading, a hairline under it', function (): void {
    $markup = '<x-operator::port-row tone="fine" name="Sonarr" said="Running" figure="3d" goes="\'/services/sonarr\'" />';
    $drawn = WhatMarkupDraws::drawn(sprintf('<native:column>%s</native:column>', $markup));

    expect(data_get($drawn, 'children.0.type'))->toBe('pressable')
        ->and(data_get($drawn, 'children.0.props.a11y_label'))->toBe('Sonarr')
        ->and(data_get($drawn, 'children.0.layout.min_height'))->toEqual(48)
        ->and(data_get($drawn, 'children.0.children.0.children.0.props.name'))->toBe(Tone::Fine->glyph())
        ->and(data_get($drawn, 'children.0.children.1.children.0.props.text'))->toBe('Sonarr')
        ->and(data_get($drawn, 'children.0.children.1.children.0.props.font_name'))->toBe(Typeface::InterfaceDisplay->value)
        ->and(data_get($drawn, 'children.0.children.1.children.1.props.text'))->toBe('Running')
        ->and(data_get($drawn, 'children.0.children.2.props.font_name'))->toBe(Typeface::Figures->value)
        ->and(data_get($drawn, 'children.0.children.3.props.name'))->toBe('chevron_right')
        ->and(data_get($drawn, 'children.1.type'))->toBe('divider');
});

it('draws a port row that goes nowhere as a plain row with no chevron, read as its name', function (): void {
    $drawn = WhatMarkupDraws::drawn('<native:column><x-operator::port-row tone="quiet" name="Bazarr" /></native:column>');

    expect(data_get($drawn, 'children.0.type'))->toBe('row')
        ->and(data_get($drawn, 'children.0.children'))->toHaveCount(2)
        ->and(data_get($drawn, 'children.0.children.1.children'))->toHaveCount(1);
});

it('reads a port row that does something by the name it answers to', function (): void {
    $drawn = WhatMarkupDraws::drawn('<native:column><x-operator::port-row tone="attention" name="Open" answers-to="Open Sonarr" tap="open" /></native:column>');

    expect(data_get($drawn, 'children.0.type'))->toBe('pressable')
        ->and(data_get($drawn, 'children.0.props.a11y_label'))->toBe('Open Sonarr')
        ->and(data_get($drawn, 'children.0.on_press'))->toBeInt();
});

it('labels a group as the operator\'s theme does: small, upper case and faint', function (): void {
    $heading = WhatMarkupDraws::drawnIn(WhoseTheme::Operator, '<x-operator::heading>Services</x-operator::heading>');

    expect(data_get($heading, 'props.font_size'))->toEqual(TypeSize::Eyebrow->points())
        ->and(data_get($heading, 'props.font_name'))->toBe(Typeface::Interface->value)
        ->and(data_get($heading, 'props.color'))->toBe(ThemeToken::Faint->in(WhoseTheme::Operator))
        ->and(data_get($heading, 'props.text_transform'))->toBe(1);
});

it('sets a quiet action in lemon on the operator\'s screens and in muted text on a member\'s', function (WhoseTheme $theme, string $colour): void {
    $action = WhatMarkupDraws::drawnIn($theme, '<x-operator::quiet-action label="Skip" tap="skip" />');

    expect(data_get($action, 'children.0.props.color'))->toBe($colour);
})->with([
    'the operator\'s' => [WhoseTheme::Operator, ThemeToken::Accent->in(WhoseTheme::Operator)],
    'a member\'s' => [WhoseTheme::Member, ThemeToken::Muted->in(WhoseTheme::Member)],
]);
