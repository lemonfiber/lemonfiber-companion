<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\WhatWasHeard;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmWasHeard
{
    public function __construct(public string $said) {}
}

/** Which arm an answer takes, and what it carried there. */
function theWordForWhichArmWasHeard(WhatWasHeard $heard): string
{
    return $heard->either(
        nothing: static fn(): WhichArmWasHeard => new WhichArmWasHeard('nothing'),
        alive: static fn(): WhichArmWasHeard => new WhichArmWasHeard('alive'),
        said: static fn(TheHealthSummary $summary): WhichArmWasHeard => new WhichArmWasHeard(sprintf('said %s', $summary->standing()->value)),
        closed: static fn(): WhichArmWasHeard => new WhichArmWasHeard('closed'),
        met: static fn(Obstacle $why): WhichArmWasHeard => new WhichArmWasHeard(sprintf('met %s', $why->kind()->value)),
    )->said;
}

it('keeps the five things a subscription can answer apart', function (): void {
    expect(theWordForWhichArmWasHeard(WhatWasHeard::nothing()))->toBe('nothing')
        ->and(theWordForWhichArmWasHeard(WhatWasHeard::aSignOfLife()))->toBe('alive')
        ->and(theWordForWhichArmWasHeard(WhatWasHeard::said(TheHealthSummary::of(HowItStands::Stopped, 0, '', WhatStoppedMoving::nothing()))))->toBe('said stopped')
        ->and(theWordForWhichArmWasHeard(WhatWasHeard::closed()))->toBe('closed')
        ->and(theWordForWhichArmWasHeard(WhatWasHeard::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))))->toBe('met credential_refused');
});
