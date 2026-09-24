<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheRoots;
use Modules\Kernel\Api\WhatIsBeside;
use Modules\Kernel\Api\WhatIsKept;
use Modules\Kernel\Api\WhatThisMachineKeeps;
use Modules\Kernel\Api\WhatWasFoundKept;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheKeepingTook
{
    public function __construct(public string $said) {}
}

/** Which arm an answer takes, and what it carried there. */
function whatWasFoundKept(WhatWasFoundKept $answer): string
{
    return $answer->either(
        kept: static fn(WhatThisMachineKeeps $keeps): WhichArmTheKeepingTook => new WhichArmTheKeepingTook(sprintf('kept:%d', count($keeps->kept()))),
        met: static fn(Obstacle $why): WhichArmTheKeepingTook => new WhichArmTheKeepingTook(sprintf('met:%s', $why->value)),
    )->said;
}

it('N6-R7 — a machine that could not be asked is never one keeping nothing', function (): void {
    $nothing = WhatThisMachineKeeps::of(TheRoots::of(), WhatIsKept::of(), WhatIsBeside::of());

    expect(whatWasFoundKept(WhatWasFoundKept::kept($nothing)))->toBe('kept:0')
        ->and(whatWasFoundKept(WhatWasFoundKept::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
