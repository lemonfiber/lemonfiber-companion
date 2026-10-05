<?php

declare(strict_types=1);

namespace Modules\Design\Tests\Api;

use function expect;
use function it;

use Modules\Design\Api\Theme;
use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhoseTheme;

// The resolver, called as EDGE calls it: a bare token name in, a hex or null
// out.

it('resolves every role to its hex in the theme it was built for', function (WhoseTheme $theme): void {
    $resolve = Theme::resolver($theme);

    foreach (ThemeToken::cases() as $token) {
        expect($resolve($token->value))->toBe($token->in($theme));
    }
})->with(WhoseTheme::cases());

it('resolves nothing for a name it does not assert', function (WhoseTheme $theme): void {
    $resolve = Theme::resolver($theme);

    expect($resolve('background'))->toBeNull()
        ->and($resolve('on-surface'))->toBeNull()
        ->and($resolve('primary'))->toBeNull()
        ->and($resolve(''))->toBeNull();
})->with(WhoseTheme::cases());
