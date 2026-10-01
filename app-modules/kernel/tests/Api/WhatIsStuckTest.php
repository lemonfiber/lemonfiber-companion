<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowMuchIsShown;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\Stalled;
use Modules\Kernel\Api\Stuck;
use Modules\Kernel\Api\WhatIsStuck;
use Modules\Kernel\Api\WhatIsUnsupported;

/** One word carried out of an `either()` arm. */
final readonly class WhatTheStallSaid
{
    public function __construct(public string $said) {}
}

/** Whichever arm an answer takes, as a word. */
function whatCameBackAboutTheStall(WhatIsStuck $answer): string
{
    return $answer->either(
        these: static fn(Stalled $stalled): WhatTheStallSaid
            => new WhatTheStallSaid(sprintf('%d stuck', $stalled->count())),
        met: static fn(Obstacle $why): WhatTheStallSaid => new WhatTheStallSaid($why->kind()->value),
    )->said;
}

it('N2-R9 — a listing takes the arm that renders rows', function (): void {
    $stalled = Stalled::of(
        HowMuchIsShown::AllOfIt,
        WhatIsUnsupported::none(),
        Stuck::at('A film nobody has seen', 'radarr', Stage::Searching),
    );

    expect(whatCameBackAboutTheStall(WhatIsStuck::these($stalled)))->toBe('1 stuck');
});

it('N1-R10 — an obstacle takes the other arm, carrying which one it was', function (): void {
    expect(whatCameBackAboutTheStall(WhatIsStuck::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))
        ->toEqual(KindOfObstacle::StackDidNotAnswer->value);
});

it('nothing stuck is not the same answer as a stack that could not be asked', function (): void {
    // The distinction this type exists for. Folding the two together would have
    // a phone in flight mode report a house where everything is arriving
    // normally, which is the one wrong answer nobody would go and check.
    expect(whatCameBackAboutTheStall(WhatIsStuck::these(Stalled::nothing())))->toBe('0 stuck')
        ->and(whatCameBackAboutTheStall(WhatIsStuck::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork))))
        ->toEqual(KindOfObstacle::DeviceHasNoNetwork->value);
});
