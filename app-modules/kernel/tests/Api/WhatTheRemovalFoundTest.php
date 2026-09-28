<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\RemovalSaysNothing;
use Modules\Kernel\Api\WhatTheRemovalFound;

it('keeps each sentence in the stack\'s order, counted', function (): void {
    $found = WhatTheRemovalFound::of(...['b' => 'The request service did not answer', 'a' => 'Her account there is still held']);

    expect(iterator_to_array($found, preserve_keys: true))->toBe(['The request service did not answer', 'Her account there is still held'])
        ->and($found)->toHaveCount(2)
        ->and(WhatTheRemovalFound::of())->toHaveCount(0);
});

it('refuses a blank sentence among real ones', function (): void {
    expect(fn(): WhatTheRemovalFound => WhatTheRemovalFound::of('It went', ' '))->toThrow(RemovalSaysNothing::class, 'its `findings` blank');
});
