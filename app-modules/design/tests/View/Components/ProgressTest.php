<?php

declare(strict_types=1);

use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('draws how far something has got as the platform\'s bar, read aloud as its words', function (): void {
    $bar = WhatMarkupDraws::drawn('<x-design::progress :done="0.25" said="700 MB of 2 GB written" />');

    expect(data_get($bar, 'props.value'))->toBe(0.25)
        ->and(data_get($bar, 'props.a11y_label'))->toBe('700 MB of 2 GB written');
});

it('draws a figure past either end at the nearer end', function (float $done, float $drawn): void {
    expect(data_get(WhatMarkupDraws::drawn(sprintf('<x-design::progress :done="%F" said="Written" />', $done)), 'props.value'))->toBe($drawn);
})->with([
    'past all of it' => [1.5, 1.0],
    'below nothing' => [-0.5, 0.0],
]);
