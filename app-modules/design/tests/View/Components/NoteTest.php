<?php

declare(strict_types=1);

use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhoseTheme;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('paints a note in the muted role of the theme on view, the same whatever the phone is set to', function (): void {
    $note = WhatMarkupDraws::drawn('<x-design::note>Quiet</x-design::note>');

    expect(data_get($note, 'props.color'))->toBe(ThemeToken::Muted->in(WhoseTheme::Member))
        ->and(data_get($note, 'props.dark_color'))->toBeNull();
});

it('is read aloud as what it shows where it is given nothing else', function (): void {
    expect(data_get(WhatMarkupDraws::drawn('<x-design::note>21:39:01</x-design::note>'), 'props.a11y_label'))->toBeNull();
});

it('is read aloud in full where what it shows is a shortening', function (): void {
    $note = WhatMarkupDraws::drawn('<x-design::note answers-to="2026-09-29T21:39:01.530691525Z">21:39:01</x-design::note>');

    expect(data_get($note, 'props.a11y_label'))->toBe('2026-09-29T21:39:01.530691525Z')
        ->and(data_get($note, 'props.text'))->toBe('21:39:01');
});
