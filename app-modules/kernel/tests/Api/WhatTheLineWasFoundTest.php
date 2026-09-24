<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\WhatTheLineWasFound;
use Modules\Kernel\Api\WhereTheLineStands;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheLineTook
{
    public function __construct(public string $said) {}
}

/** Which arm an answer takes, and what it carried there. */
function whatTheLineWasFound(WhatTheLineWasFound $answer): string
{
    return $answer->either(
        shared: static fn(HowTheLineIsShared $line): WhichArmTheLineTook => new WhichArmTheLineTook(sprintf('shared:%s', $line->stands()->value)),
        met: static fn(Obstacle $why): WhichArmTheLineTook => new WhichArmTheLineTook(sprintf('met:%s', $why->value)),
    )->said;
}

it('N10-R12 — a line that could not be read is never an unlimited one', function (): void {
    expect(whatTheLineWasFound(WhatTheLineWasFound::shared(HowTheLineIsShared::standing(WhereTheLineStands::Unlimited, 'Nothing holds the stack back', 'Down: no limit', 'Up: no limit', Remarks::of(), Remarks::of()))))->toBe('shared:unlimited')
        ->and(whatTheLineWasFound(WhatTheLineWasFound::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
