<?php

declare(strict_types=1);

use Modules\Design\View\Tone;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('draws the line beside its tone\'s glyph, the glyph read aloud as its word', function (): void {
    $row = WhatMarkupDraws::drawn('<x-design::marked-line line="VPN provider name is not valid" tone="trouble" word="Error" />');

    expect(data_get($row, 'children.0.props.name'))->toBe(Tone::Trouble->glyph())
        ->and(data_get($row, 'children.0.props.a11y_label'))->toBe('Error')
        ->and(data_get($row, 'children.1.props.text'))->toBe('VPN provider name is not valid');
});
