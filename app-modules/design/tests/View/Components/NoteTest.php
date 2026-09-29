<?php

declare(strict_types=1);

use Modules\Design\Api\ThemeToken;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('paints every text role with its light colour and its dark one', function (): void {
    $note = WhatMarkupDraws::drawn('<x-design::note>Quiet</x-design::note>');

    expect(data_get($note, 'props.color'))->toBe(ThemeToken::Muted->light())
        ->and(data_get($note, 'props.dark_color'))->toBe(ThemeToken::Muted->dark());
});
