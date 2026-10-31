<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ALeftBehind;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\FillSaysNothing;
use Modules\Kernel\Api\WhatStaysBehind;

it('carries what stays behind in the stack\'s words, with the capability that declares it', function (): void {
    $left = ALeftBehind::of(Capability::called('media-server'), ' the accounts Jellyfin holds ');

    expect([$left->capability()->named(), $left->said()])->toBe(['media-server', 'the accounts Jellyfin holds']);
});

it('refuses what stays behind said in no words', function (): void {
    expect(fn(): ALeftBehind => ALeftBehind::of(Capability::called('media-server'), ' '))->toThrow(FillSaysNothing::class, '`said` blank');
});

it('keeps what stays behind in the stack\'s order, and nothing as an answer', function (): void {
    $accounts = ALeftBehind::of(Capability::called('media-server'), 'the accounts Jellyfin holds');
    $watched = ALeftBehind::of(Capability::called('media-server'), 'what each member watched');

    expect(iterator_to_array(WhatStaysBehind::these(second: $watched, first: $accounts), preserve_keys: false))->toBe([$watched, $accounts])
        ->and(iterator_to_array(WhatStaysBehind::these(first: $accounts), preserve_keys: true))->toBe([0 => $accounts])
        ->and(WhatStaysBehind::none()->count())->toBe(0)
        ->and(WhatStaysBehind::these($accounts, $watched)->count())->toBe(2);
});
