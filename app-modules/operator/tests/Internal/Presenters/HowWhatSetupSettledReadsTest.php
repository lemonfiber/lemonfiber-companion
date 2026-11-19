<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Operator\Internal\Presenters\HowWhatSetupSettledReads;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatSetupSettledAsShown;

it('draws nothing of what setup settled where the session ended or the stack could not be asked', function (): void {
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);

    expect(new HowWhatSetupSettledReads()->signedOut())->toEqual(new WhatSetupSettledAsShown(HowTheReadingWent::theSessionEnded(), finished: false, dataRoot: '', downloadingSaid: '', runsAs: '', runsAsFromSaid: ''))
        ->and(new HowWhatSetupSettledReads()->met($why))->toEqual(new WhatSetupSettledAsShown(HowTheReadingWent::somethingStopped($why), finished: false, dataRoot: '', downloadingSaid: '', runsAs: '', runsAsFromSaid: ''));
});
