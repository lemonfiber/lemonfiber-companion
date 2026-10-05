<?php

declare(strict_types=1);

use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\Typeface;
use Modules\Design\Api\TypeSize;
use Modules\Design\Api\WhoseTheme;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('labels a group as the operator\'s theme does: small, upper case and faint', function (): void {
    $heading = WhatMarkupDraws::drawnIn(WhoseTheme::Operator, '<x-operator::heading>Services</x-operator::heading>');

    expect(data_get($heading, 'props.font_size'))->toEqual(TypeSize::Eyebrow->points())
        ->and(data_get($heading, 'props.font_name'))->toBe(Typeface::Interface->value)
        ->and(data_get($heading, 'props.color'))->toBe(ThemeToken::Faint->in(WhoseTheme::Operator))
        ->and(data_get($heading, 'props.text_transform'))->toBe(1);
});
