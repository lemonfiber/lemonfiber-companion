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
    'title' => ['title', 'text-[27] font-extrabold text-theme-text', Typeface::InterfaceDisplay],
    'heading' => ['heading', 'text-[15] font-extrabold text-theme-text', Typeface::InterfaceDisplay],
    'body' => ['body', 'text-[15] font-medium text-theme-text', Typeface::Interface],
    'note' => ['note', 'text-[13] font-medium text-theme-muted', Typeface::Interface],
    'strong' => ['strong', 'text-[15] font-extrabold text-theme-text', Typeface::InterfaceDisplay],
    'verbatim' => ['verbatim', 'text-[13] text-theme-text', Typeface::Figures],
]);
