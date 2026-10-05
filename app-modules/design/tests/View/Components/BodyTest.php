<?php

declare(strict_types=1);

use Modules\Design\Api\Typeface;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('sets each text role in its size, weight, face and colour role', function (string $tag, string $classes, Typeface $face): void {
    $expected = WhatMarkupDraws::drawn(sprintf('<native:text class="%s" font="%s">Words</native:text>', $classes, $face->value));

    expect(WhatMarkupDraws::drawn(sprintf('<x-design::%s>Words</x-design::%s>', $tag, $tag)))->toEqual($expected);
})->with([
    'title' => ['title', 'text-2xl font-bold text-theme-text', Typeface::InterfaceBold],
    'heading' => ['heading', 'text-lg font-semibold text-theme-text', Typeface::InterfaceSemiBold],
    'body' => ['body', 'text-base text-theme-text', Typeface::Interface],
    'note' => ['note', 'text-sm text-theme-muted', Typeface::Interface],
    'strong' => ['strong', 'font-semibold text-theme-text', Typeface::InterfaceSemiBold],
    'verbatim' => ['verbatim', 'text-sm text-theme-text', Typeface::Figures],
]);
