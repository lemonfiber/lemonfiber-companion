<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\ViewModels;

use function expect;
use function it;

use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheVersionsSpoken;
use Modules\Operator\Internal\ViewModels\WhatAnObstacleNames;

it('fills in both versions where the two disagree', function (): void {
    expect(WhatAnObstacleNames::in(Obstacle::versionsDisagree(TheVersionsSpoken::between(answered: 2, spoken: 1))))
        ->toBe(['answered' => 2, 'spoken' => 1]);
});

it('fills in nothing for a kind that names nothing', function (): void {
    expect(WhatAnObstacleNames::in(Obstacle::of(KindOfObstacle::StackIsBusy)))->toBe([]);
});
