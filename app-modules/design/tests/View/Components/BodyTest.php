<?php

declare(strict_types=1);

use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('sets each text role in its size, weight and colour role', function (string $tag, string $classes): void {
    $expected = WhatMarkupDraws::drawn(sprintf('<native:text class="%s">Words</native:text>', $classes));

    expect(WhatMarkupDraws::drawn(sprintf('<x-design::%s>Words</x-design::%s>', $tag, $tag)))->toEqual($expected);
})->with([
    'title' => ['title', 'text-2xl font-bold text-theme-text'],
    'heading' => ['heading', 'text-lg font-semibold text-theme-text'],
    'body' => ['body', 'text-base text-theme-text'],
    'note' => ['note', 'text-sm text-theme-muted'],
    'strong' => ['strong', 'font-semibold text-theme-text'],
    'verbatim' => ['verbatim', 'font-mono text-sm text-theme-text'],
]);
