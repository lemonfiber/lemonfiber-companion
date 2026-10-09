<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use ArrayObject;

use function expect;
use function it;

use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatTheGrantCameTo;

use function str_repeat;

it('answers a grant on the granted arm and an obstacle on the refused one', function (): void {
    $grant = AGrant::of(str_repeat('a', 32), Instant::atEpochSeconds(1));
    $why = Obstacle::of(KindOfObstacle::CredentialWasRefused);

    $granted = WhatTheGrantCameTo::granted($grant)->either(
        granted: static fn(AGrant $given): ArrayObject => new ArrayObject([$given]),
        refused: static fn(): ArrayObject => new ArrayObject([]),
    );
    $refused = WhatTheGrantCameTo::refused($why)->either(
        granted: static fn(): ArrayObject => new ArrayObject([]),
        refused: static fn(Obstacle $given): ArrayObject => new ArrayObject([$given]),
    );

    expect($granted->getArrayCopy())->toBe([$grant])
        ->and($refused->getArrayCopy())->toBe([$why]);
});
