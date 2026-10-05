<?php

declare(strict_types=1);

use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhoseTheme;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('draws a rule as the platform\'s divider in the line role', function (): void {
    $rule = WhatMarkupDraws::drawn('<x-operator::rule />');

    expect(data_get($rule, 'type'))->toBe('divider')
        ->and(data_get($rule, 'style.border_color'))->toBe(ThemeToken::Line->in(WhoseTheme::Member));
});
