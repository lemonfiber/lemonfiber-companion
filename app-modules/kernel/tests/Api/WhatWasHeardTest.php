<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatWasHeard;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmWasHeard
{
    public function __construct(public string $said) {}
}

/** Which arm an answer takes, and what it carried there. */
function whichArmWasHeard(WhatWasHeard $heard): string
{
    return $heard->either(
        nothing: static fn(): WhichArmWasHeard => new WhichArmWasHeard('nothing'),
        alive: static fn(): WhichArmWasHeard => new WhichArmWasHeard('alive'),
        said: static fn(TheHealthSummary $summary): WhichArmWasHeard => new WhichArmWasHeard(sprintf('said %s', $summary->standing()->value)),
        closed: static fn(): WhichArmWasHeard => new WhichArmWasHeard('closed'),
        met: static fn(Obstacle $why): WhichArmWasHeard => new WhichArmWasHeard(sprintf('met %s', $why->value)),
    )->said;
}

it('keeps the five things a subscription can answer apart', function (): void {
    expect(whichArmWasHeard(WhatWasHeard::nothing()))->toBe('nothing')
        ->and(whichArmWasHeard(WhatWasHeard::aSignOfLife()))->toBe('alive')
        ->and(whichArmWasHeard(WhatWasHeard::said(TheHealthSummary::of(HowItStands::Stopped, 0, ''))))->toBe('said stopped')
        ->and(whichArmWasHeard(WhatWasHeard::closed()))->toBe('closed')
        ->and(whichArmWasHeard(WhatWasHeard::met(Obstacle::CredentialWasRefused)))->toBe('met credential_refused');
});
