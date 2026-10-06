<?php

declare(strict_types=1);

use Modules\Household\Internal\ViewModels\WhatAMemberIsTold;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Tests\TestCase;

// The catalogue is read, so the application is booted.
uses(TestCase::class);

it('tells a member a house too old for what they opened that the house needs an update', function (): void {
    $why = Obstacle::of(KindOfObstacle::NotOnThisStack);

    expect(WhatAMemberIsTold::met($why))->toBe('household.needs_an_update')
        ->and(WhatAMemberIsTold::remedy($why))->toBe('household.needs_an_update_action');
});

it('tells a member everything else in the obstacle\'s own sentences', function (): void {
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);

    expect(WhatAMemberIsTold::met($why))->toBe($why->said())
        ->and(WhatAMemberIsTold::remedy($why))->toBe($why->remedy());
});

it('never names the software or a version to a member', function (): void {
    $why = Obstacle::of(KindOfObstacle::NotOnThisStack);

    expect(__(WhatAMemberIsTold::met($why)))->not->toContain('lemonfiber')
        ->and(__(WhatAMemberIsTold::remedy($why)))->not->toContain('lemonfiber');
});
