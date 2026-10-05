<?php

declare(strict_types=1);

use Modules\Design\Api\Typeface;
use Modules\Design\View\Tone;
use Modules\Operator\View\Components\PortRow;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('draws a port row as one target ending in a chevron, read as its name and what is said about it, its port leading, a hairline under it', function (): void {
    $markup = '<x-operator::port-row tone="fine" name="Sonarr" said="Running" figure="3d" goes="\'/services/sonarr\'" />';
    $drawn = WhatMarkupDraws::drawn(sprintf('<native:column>%s</native:column>', $markup));

    expect(data_get($drawn, 'children.0.type'))->toBe('pressable')
        ->and(data_get($drawn, 'children.0.props.a11y_label'))->toBe(sprintf('Sonarr%sRunning', PortRow::BETWEEN))
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
