<?php

declare(strict_types=1);

use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhoseTheme;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

it('sets a quiet action in lemon on the operator\'s screens and in muted text on a member\'s', function (WhoseTheme $theme, string $colour): void {
    $action = WhatMarkupDraws::drawnIn($theme, '<x-operator::quiet-action label="Skip" tap="skip" />');

    expect(data_get($action, 'children.0.props.color'))->toBe($colour);
})->with([
    'the operator\'s' => [WhoseTheme::Operator, ThemeToken::Accent->in(WhoseTheme::Operator)],
    'a member\'s' => [WhoseTheme::Member, ThemeToken::Muted->in(WhoseTheme::Member)],
]);
