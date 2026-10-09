<?php

declare(strict_types=1);

use Modules\Household\Internal\ViewModels\WhatAMemberIsTold;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatTheHouseholdWasTold;
use Tests\TestCase;

// The catalogue is read, so the application is booted.
uses(TestCase::class);

it('tells a member a house too old for what they opened that the house needs an update', function (): void {
    $why = Obstacle::of(KindOfObstacle::NotOnThisStack);

    expect(WhatAMemberIsTold::met($why))->toBe('household.needs_an_update')
        ->and(WhatAMemberIsTold::remedy($why))->toBe('household.needs_an_update_action');
});

it('tells a member everything else as the household is told it', function (KindOfObstacle $kind): void {
    $why = Obstacle::of($kind);

    expect(WhatAMemberIsTold::met($why))->toBe($why->saidToTheHousehold())
        ->and(WhatAMemberIsTold::remedy($why))->toBe($why->remedyForTheHousehold());
})->with([
    'a reach that met nothing, in the household\'s words' => [KindOfObstacle::StackDidNotAnswer],
    'anything else, in the obstacle\'s own' => [KindOfObstacle::TooManyAttempts],
]);

it('never names the software or a version to a member', function (): void {
    $why = Obstacle::of(KindOfObstacle::NotOnThisStack);

    expect(__(WhatAMemberIsTold::met($why)))->not->toContain('lemonfiber')
        ->and(__(WhatAMemberIsTold::remedy($why)))->not->toContain('lemonfiber');
});

it('tells a member the core\'s own sentence and remedy wherever it wrote them, a house too old included', function (KindOfObstacle $kind): void {
    $why = Obstacle::of($kind)->withWhatTheHouseholdWasTold(WhatTheHouseholdWasTold::said('Your library is not answering right now.', 'Try again in a little while'));

    expect(WhatAMemberIsTold::met($why))->toBe('Your library is not answering right now.')
        ->and(WhatAMemberIsTold::remedy($why))->toBe('Try again in a little while')
        ->and(WhatAMemberIsTold::isInTheStacksWords($why))->toBeTrue()
        ->and(WhatAMemberIsTold::isInTheStacksWords(Obstacle::of($kind)))->toBeFalse();
})->with([
    'the household could not be asked' => [KindOfObstacle::MediaServerDidNotAnswer],
    'a house too old' => [KindOfObstacle::NotOnThisStack],
]);
