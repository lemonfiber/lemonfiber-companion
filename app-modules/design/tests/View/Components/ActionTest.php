<?php

declare(strict_types=1);

use Illuminate\View\ViewException;
use Modules\Design\View\Prominence;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('draws a primary action as the tall accent button', function (): void {
    $button = WhatMarkupDraws::drawn('<x-design::action label="Start it" tap="start()" />');

    expect(data_get($button, 'props.variant'))->toBe('primary')
        ->and(data_get($button, 'props.size'))->toBe('lg')
        ->and(data_get($button, 'props.a11y_label'))->toBe('Start it')
        ->and(data_get($button, 'layout.width'))->toBe('fill')
        ->and(data_get($button, 'props.on_press'))->toBeInt();
});

it('draws a tonal action as the platform\'s second button, named for what it acts on', function (): void {
    $button = WhatMarkupDraws::drawn('<x-design::action label="Stop" goes="\'/x\'" tone="tonal" answers-to="Stop Gluetun" :disabled="true" />');

    expect(data_get($button, 'props.variant'))->toBe(Prominence::Tonal->variant())
        ->and(data_get($button, 'props.a11y_label'))->toBe('Stop Gluetun')
        ->and(data_get($button, 'props.disabled'))->toBeTrue()
        ->and(data_get($button, 'on_press'))->toBeInt();
});

it('refuses an action drawn at a prominence there is none of', function (): void {
    WhatMarkupDraws::drawn('<x-design::action label="Go" tap="go()" tone="loud" />');
})->throws(ViewException::class, '"loud" is not a valid backing value for enum Modules\\Design\\View\\Prominence');

it('hands the screen it opens what it carries, besides the route', function (): void {
    $roads = WhatMarkupDraws::roads('<x-design::action label="More" goes="/more" :carries="[\'titled\' => \'Alien\']" />');

    expect($roads)->toHaveCount(1)
        ->and(data_get($roads, '0.uri'))->toBe('/more')
        ->and(data_get($roads, '0.data'))->toBe(['titled' => 'Alien']);
});

it('carries nothing where it is given nothing to carry', function (): void {
    expect(data_get(WhatMarkupDraws::roads('<x-design::action label="More" goes="/more" />'), '0.data'))->toBe([]);
});
