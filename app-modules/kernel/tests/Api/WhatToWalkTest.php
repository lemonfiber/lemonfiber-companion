<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhatToWalk;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** What a walk was asked for, as one line. */
function whatWasAskedToWalk(WhatToWalk $asked): string
{
    return $asked->either(
        named: static fn(string $item): TheWordCarriedOut => new TheWordCarriedOut(sprintf('named:%s', $item)),
        likeliest: static fn(): TheWordCarriedOut => new TheWordCarriedOut('likeliest'),
    )->said;
}

it('walks a title as typed, without the spaces around it', function (): void {
    expect(whatWasAskedToWalk(WhatToWalk::called('Sintel')))->toBe('named:Sintel')
        ->and(whatWasAskedToWalk(WhatToWalk::called('  Big Buck Bunny ')))->toBe('named:Big Buck Bunny');
});

it('leaves the choice to the stack where nothing was typed', function (string $typed): void {
    expect(whatWasAskedToWalk(WhatToWalk::called($typed)))->toBe('likeliest');
})->with(['nothing' => [''], 'only spaces' => ['   ']]);

it('is asked for by the name lemonfiber gives the action', function (): void {
    expect(WhatToWalk::called('Sintel')->asked())->toBe('walkthrough')
        ->and(WhatToWalk::called('')->asked())->toBe('walkthrough');
});
