<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HandoverSaysNothing;
use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\HowTheHandoverWent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheFilesTouched;
use Modules\Kernel\Api\WhatTheHandoverDid;

use function sprintf;

/** One word carried out of whichever arm a handover took. */
final readonly class WhatTheHandoverCameTo
{
    public function __construct(public string $said) {}
}

/** Which arm, and what it carried. Named for this file (`G10`). */
function whichWayTheHandoverWent(HowTheHandoverWent $went): string
{
    return $went->either(
        did: static fn(WhatTheHandoverDid $did): WhatTheHandoverCameTo => new WhatTheHandoverCameTo(sprintf('did %s', $did->name())),
        refused: static fn(string $said): WhatTheHandoverCameTo => new WhatTheHandoverCameTo(sprintf('refused: %s', $said)),
        met: static fn(Obstacle $why): WhatTheHandoverCameTo => new WhatTheHandoverCameTo(sprintf('met %s', $why->value)),
    )->said;
}

it('hands over what the stack did through the first arm', function (): void {
    $did = WhatTheHandoverDid::removing(name: 'watch', rehearsed: false, standing: HowItIsHosted::NotHosted, touched: TheFilesTouched::these());

    expect(whichWayTheHandoverWent(HowTheHandoverWent::did($did)))->toBe('did watch');
});

it('carries the stack\'s own words for a refusal, less the space around them', function (): void {
    // Told apart from an obstacle: the machine answered and said why, and an
    // operator sent to check their network about it has been sent the wrong way.
    expect(whichWayTheHandoverWent(HowTheHandoverWent::refused(" The guard was not told what to guard \n")))
        ->toBe('refused: The guard was not told what to guard');
});

it('carries what the operator met where nothing answered', function (): void {
    expect(whichWayTheHandoverWent(HowTheHandoverWent::met(Obstacle::StackDidNotAnswer)))->toBe('met no_answer');
});

it('refuses a refusal that says nothing', function (): void {
    expect(static fn(): HowTheHandoverWent => HowTheHandoverWent::refused('  '))
        ->toThrow(HandoverSaysNothing::class, 'why it was refused');
});
