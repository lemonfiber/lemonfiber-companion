<?php

declare(strict_types=1);

use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('draws chips side by side, wrapping, each chosen or not', function (): void {
    $markup = '<x-design::chips>'
        . '<x-design::chip label="VPN 4" tap="read(\'vpn\')" :chosen="true" answers-to="Show VPN" />'
        . '<x-design::chip label="Storage 6" tap="read(\'storage\')" />'
        . '</x-design::chips>';
    $drawn = WhatMarkupDraws::drawn($markup);

    expect(WhatMarkupDraws::outline($markup))->toBe('row{"width":"fill","gap":8,"flex_wrap":1}[chip[][], chip[][]]')
        ->and(data_get($drawn, 'children.0.props.value'))->toBeTrue()
        ->and(data_get($drawn, 'children.0.props.a11y_label'))->toBe('Show VPN')
        ->and(data_get($drawn, 'children.1.props.value'))->toBeFalse()
        ->and(data_get($drawn, 'children.1.props.a11y_label'))->toBe('Storage 6')
        ->and(data_get($drawn, 'children.1.props.on_change'))->toBeInt();
});
