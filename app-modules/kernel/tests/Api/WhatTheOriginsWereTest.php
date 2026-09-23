<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatTheOriginsWere;
use Modules\Kernel\Api\WhereTheServicesComeFrom;

use function sprintf;

/** One word carried out of an arm. */
final readonly class WhichArmTheOriginsTook
{
    public function __construct(public string $said) {}
}

/** Which arm an answer takes, and what it carried there. */
function whatTheOriginsSaid(WhatTheOriginsWere $answer): string
{
    return $answer->either(
        origins: static fn(WhereTheServicesComeFrom $origins): WhichArmTheOriginsTook => new WhichArmTheOriginsTook(sprintf('origins:%d', $origins->count())),
        met: static fn(Obstacle $why): WhichArmTheOriginsTook => new WhichArmTheOriginsTook(sprintf('met:%s', $why->value)),
    )->said;
}

it('a stack declaring nothing is an answer, not an obstacle', function (): void {
    expect(whatTheOriginsSaid(WhatTheOriginsWere::origins(WhereTheServicesComeFrom::declaring())))->toBe('origins:0');
});

it('carries what the operator met where the stack could not be asked', function (): void {
    expect(whatTheOriginsSaid(WhatTheOriginsWere::met(Obstacle::StackDidNotAnswer)))
        ->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
