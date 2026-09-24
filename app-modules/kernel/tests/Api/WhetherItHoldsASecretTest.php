<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhetherItHoldsASecret;

it('N6-R7 — reads the wire\'s flag one way round', function (): void {
    expect(WhetherItHoldsASecret::said(secret: true))->toBe(WhetherItHoldsASecret::Secret)
        ->and(WhetherItHoldsASecret::said(secret: false))->toBe(WhetherItHoldsASecret::Plain);
});

it('names a catalogue key for each case', function (): void {
    expect(WhetherItHoldsASecret::Secret->saidOnTheScreen())->toBe('stacks.keeps.secret.secret')
        ->and(WhetherItHoldsASecret::Plain->saidOnTheScreen())->toBe('stacks.keeps.secret.plain');
});
