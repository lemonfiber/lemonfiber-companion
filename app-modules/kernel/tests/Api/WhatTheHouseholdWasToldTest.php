<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RefusalSaysNothing;
use Modules\Kernel\Api\WhatTheHouseholdWasTold;

it('keeps the core\'s sentence and remedy as written, trimmed, and refuses a blank sentence', function (): void {
    $told = WhatTheHouseholdWasTold::said('  Your library is not answering right now.  ', '  ');

    expect([$told->wasSaid(), $told->sentence(), $told->remedy()])->toBe([true, 'Your library is not answering right now.', ''])
        ->and(static fn(): WhatTheHouseholdWasTold => WhatTheHouseholdWasTold::said(' ', 'Try again'))->toThrow(RefusalSaysNothing::class, 'summary')
        ->and(WhatTheHouseholdWasTold::nothing()->wasSaid())->toBeFalse();
});

it('is carried by an obstacle without changing what kind it is, and an obstacle given none carries nothing', function (): void {
    $met = Obstacle::of(KindOfObstacle::MediaServerDidNotAnswer);
    $told = $met->withWhatTheHouseholdWasTold(WhatTheHouseholdWasTold::said('Your library is not answering right now.', 'Try again in a little while'));

    expect($told->kind())->toBe(KindOfObstacle::MediaServerDidNotAnswer)
        ->and($told->meansWeAreSignedOut())->toBeFalse()
        ->and($told->whatTheHouseholdWasTold()->sentence())->toBe('Your library is not answering right now.')
        ->and($met->whatTheHouseholdWasTold()->wasSaid())->toBeFalse();
});
