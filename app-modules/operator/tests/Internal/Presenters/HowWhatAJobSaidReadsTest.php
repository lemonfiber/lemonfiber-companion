<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\WhatAJobSaid;
use Modules\Kernel\Api\WhatWasHeardFromAJob;
use Modules\Operator\Internal\Presenters\HowWhatAJobSaidReads;

it('draws only the newest line until every line is asked for', function (): void {
    $shown = new HowWhatAJobSaidReads()->of(WhatAJobSaid::nothing()->heard(WhatWasHeardFromAJob::said('Pulling sonarr', 'Starting sonarr')), showingAll: false);

    expect($shown->newest)->toBe('Starting sonarr')
        ->and($shown->lines)->toBe([])
        ->and($shown->showingAll)->toBeFalse()
        ->and($shown->kept)->toBe(HowManyLines::ON_A_PHONE)
        ->and($shown->letGo)->toBe(0);
});

it('draws every line kept, oldest first, once they are asked for', function (): void {
    $shown = new HowWhatAJobSaidReads()->of(WhatAJobSaid::nothing()->heard(WhatWasHeardFromAJob::said('Pulling sonarr', 'Starting sonarr')), showingAll: true);

    expect($shown->lines)->toBe(['Pulling sonarr', 'Starting sonarr'])
        ->and($shown->showingAll)->toBeTrue();
});

it('draws nothing of a job that has said nothing', function (): void {
    expect(new HowWhatAJobSaidReads()->of(WhatAJobSaid::nothing(), showingAll: true)->newest)->toBeNull();
});
