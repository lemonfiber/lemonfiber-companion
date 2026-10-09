<?php

declare(strict_types=1);

namespace Modules\Household\Tests\Internal\ViewModels;

use function expect;
use function it;

use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToHaveAsked;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheVersionsSpoken;
use Modules\Kernel\Api\WhatTheHouseholdWasTold;

it('fills its obstacle sentences with the versions that disagreed', function (): void {
    $met = Obstacle::versionsDisagree(TheVersionsSpoken::between(answered: 2, spoken: 1));

    expect(WhatAMemberTurnedOutToHaveAsked::somethingStopped($met)->filling())->toBe(['answered' => 2, 'spoken' => 1]);
});

it('fills in nothing where nothing stood in the way', function (): void {
    expect(WhatAMemberTurnedOutToHaveAsked::these([])->filling())->toBe([]);
});

it('is put right in the app\'s settings only where the local network was refused', function (): void {
    expect([
        WhatAMemberTurnedOutToHaveAsked::somethingStopped(Obstacle::of(KindOfObstacle::LocalNetworkIsNotPermitted))->isPutRightInTheAppsSettings(),
        WhatAMemberTurnedOutToHaveAsked::somethingStopped(Obstacle::of(KindOfObstacle::StackDidNotAnswer))->isPutRightInTheAppsSettings(),
        WhatAMemberTurnedOutToHaveAsked::these([])->isPutRightInTheAppsSettings(),
    ])->toBe([true, false, false]);
});

it('says what they asked for could not be read in the core\'s own words where it wrote any, and in this app\'s otherwise', function (): void {
    $told = Obstacle::of(KindOfObstacle::MediaServerDidNotAnswer)
        ->withWhatTheHouseholdWasTold(WhatTheHouseholdWasTold::said('Your library is not answering right now.', 'Try again in a little while'));
    $inItsWords = WhatAMemberTurnedOutToHaveAsked::somethingStopped($told);
    $inOurs = WhatAMemberTurnedOutToHaveAsked::somethingStopped(Obstacle::of(KindOfObstacle::MediaServerDidNotAnswer));

    expect([$inItsWords->isInTheStacksWords(), $inItsWords->met, $inItsWords->remedy])->toBe([true, 'Your library is not answering right now.', 'Try again in a little while'])
        ->and([$inOurs->isInTheStacksWords(), $inOurs->met])->toBe([false, KindOfObstacle::MediaServerDidNotAnswer->saidToTheHousehold()])
        ->and(WhatAMemberTurnedOutToHaveAsked::theSessionEnded()->isInTheStacksWords())->toBeFalse();
});
