<?php

declare(strict_types=1);

use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('draws a card as a padded card around what it holds', function (): void {
    $markup = '<x-design::card><x-design::strong>A</x-design::strong><x-design::note>B</x-design::note></x-design::card>';

    expect(WhatMarkupDraws::outline($markup))->toBe('column{"width":"fill","padding":16,"gap":8}[A, B]')
        ->and(data_get(WhatMarkupDraws::drawn($markup), 'props.dark_bg_color'))->toBeNull();
});
