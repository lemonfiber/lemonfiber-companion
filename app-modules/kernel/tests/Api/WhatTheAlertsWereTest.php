<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SetApart;
use Modules\Kernel\Api\WhatTheAlertsWere;
use Modules\Kernel\Api\WhatTheOperatorIsTold;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheAlertsTook
{
    public function __construct(public string $said) {}
}

/** Which arm an answer takes, and what it carried there. */
function whatTheAlertsSaid(WhatTheAlertsWere $answer): string
{
    return $answer->either(
        told: static fn(WhatTheOperatorIsTold $told): WhichArmTheAlertsTook => new WhichArmTheAlertsTook(sprintf('told:%s', $told->preset())),
        met: static fn(Obstacle $why): WhichArmTheAlertsTook => new WhichArmTheAlertsTook(sprintf('met:%s', $why->value)),
    )->said;
}

it('N10-R12 — carries what the operator is told, or what stood in the way, never one for the other', function (): void {
    expect(whatTheAlertsSaid(WhatTheAlertsWere::told(WhatTheOperatorIsTold::byPreset('quiet', 'Only what needs you today', SetApart::of()))))->toBe('told:quiet')
        ->and(whatTheAlertsSaid(WhatTheAlertsWere::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
