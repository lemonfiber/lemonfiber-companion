<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\StartSaysNothing;
use Modules\Kernel\Api\WhatAStartWaitsOn;

use function sprintf;

/** Which arm an answer about a start takes, and what it carried there. */
function whichArmAStartTook(WhatAStartWaitsOn $heard): string
{
    return $heard->either(
        saying: static fn(string $line): Code => Code::of(sprintf('saying %s', $line)),
        nothingNew: static fn(): Code => Code::of('nothing new'),
        met: static fn(Obstacle $why): Code => Code::of(sprintf('met %s', $why->kind()->value)),
    )->shown();
}

it('says the line the stack sent, trimmed, and nothing new when nothing arrived', function (): void {
    expect(whichArmAStartTook(WhatAStartWaitsOn::saying('  Waiting for the database ')))->toBe('saying Waiting for the database')
        ->and(whichArmAStartTook(WhatAStartWaitsOn::nothingNew()))->toBe('nothing new')
        ->and(whichArmAStartTook(WhatAStartWaitsOn::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met %s', KindOfObstacle::StackDidNotAnswer->value));
});

it('refuses a line that says nothing, rather than drawing a blank', function (): void {
    expect(fn(): WhatAStartWaitsOn => WhatAStartWaitsOn::saying('   '))
        ->toThrow(StartSaysNothing::class, 'blank or not text');
});
