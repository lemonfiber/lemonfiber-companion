<?php

declare(strict_types=1);

namespace Modules\Design\Tests\Api;

use function expect;
use function it;

use Modules\Design\Api\Theme;
use Modules\Design\Api\ThemeToken;

// The resolvers, called as EDGE calls them: a bare token name in, a hex or
// null out.

it('resolves every role to its light hex, and to its dark hex in the dark', function (): void {
    $light = Theme::resolver();
    $dark = Theme::darkResolver();

    foreach (ThemeToken::cases() as $token) {
        expect($light($token->value))->toBe($token->light())
            ->and($dark($token->value))->toBe($token->dark());
    }
});

it('resolves nothing for a name it does not assert', function (): void {
    foreach ([Theme::resolver(), Theme::darkResolver()] as $resolve) {
        expect($resolve('background'))->toBeNull()
            ->and($resolve('on-surface'))->toBeNull()
            ->and($resolve('primary'))->toBeNull()
            ->and($resolve(''))->toBeNull();
    }
});
