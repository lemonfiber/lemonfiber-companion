<?php

declare(strict_types=1);

namespace Modules\Household\Tests\Internal\ViewModels;

use function expect;
use function it;

use Modules\Household\Internal\ViewModels\WhatTheirOwnTitlesTurnedOutToBe;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheVersionsSpoken;

it('fills its obstacle sentences with the versions that disagreed', function (): void {
    $met = Obstacle::versionsDisagree(TheVersionsSpoken::between(answered: 2, spoken: 1));

    expect(WhatTheirOwnTitlesTurnedOutToBe::somethingStopped($met)->filling())->toBe(['answered' => 2, 'spoken' => 1])
        ->and(WhatTheirOwnTitlesTurnedOutToBe::somethingStopped($met)->wasStopped())->toBeTrue();
});

it('fills in nothing, and was not stopped, where nothing stood in the way', function (): void {
    expect(WhatTheirOwnTitlesTurnedOutToBe::these([])->filling())->toBe([])
        ->and(WhatTheirOwnTitlesTurnedOutToBe::these([])->wasStopped())->toBeFalse()
        ->and(WhatTheirOwnTitlesTurnedOutToBe::theSessionEnded()->wasStopped())->toBeFalse()
        ->and(WhatTheirOwnTitlesTurnedOutToBe::theSessionEnded()->cameBack)->toBeFalse();
});
