<?php

declare(strict_types=1);

use Modules\Design\Api\Typeface;
use Modules\Design\Api\TypeSize;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('sets what a machine wrote in the figures face at the caption size', function (): void {
    $line = WhatMarkupDraws::drawn('<x-design::verbatim>GET /health 200</x-design::verbatim>');

    expect(data_get($line, 'props.font_name'))->toBe(Typeface::Figures->value)
        ->and(data_get($line, 'props.font_size'))->toEqual(TypeSize::Caption->points());
});
