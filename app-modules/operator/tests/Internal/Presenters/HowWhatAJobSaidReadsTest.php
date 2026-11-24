<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Design\Api\ATap;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\WhatAJobSaid;
use Modules\Kernel\Api\WhatWasHeardFromAJob;
use Modules\Operator\Internal\Presenters\HowWhatAJobSaidReads;

it('draws only the newest line until every line is asked for', function (): void {
    $shown = new HowWhatAJobSaidReads()->of(WhatAJobSaid::nothing()->heard(WhatWasHeardFromAJob::said('Pulling sonarr', 'Starting sonarr')), showingAll: false, showAll: ATap::calling('showAllItSaid', 'Asking'), showTheNewest: ATap::calling('showTheNewestItSaid', 'Asking'));

    expect($shown->newest)->toBe('Starting sonarr')
        ->and($shown->lines)->toBe([])
        ->and($shown->showingAll)->toBeFalse()
        ->and($shown->kept)->toBe(HowManyLines::ON_A_PHONE)
        ->and($shown->letGo)->toBe(0)
        ->and($shown->showAll)->toBe("showAllItSaid('Asking')")
        ->and($shown->showTheNewest)->toBe("showTheNewestItSaid('Asking')");
});

it('draws every line kept, oldest first, once they are asked for', function (): void {
    $shown = new HowWhatAJobSaidReads()->of(WhatAJobSaid::nothing()->heard(WhatWasHeardFromAJob::said('Pulling sonarr', 'Starting sonarr')), showingAll: true, showAll: ATap::calling('showAllItSaid', 'Asking'), showTheNewest: ATap::calling('showTheNewestItSaid', 'Asking'));

    expect($shown->lines)->toBe(['Pulling sonarr', 'Starting sonarr'])
        ->and($shown->showingAll)->toBeTrue();
});

it('draws nothing of a job that has said nothing', function (): void {
    expect(new HowWhatAJobSaidReads()->of(WhatAJobSaid::nothing(), showingAll: true, showAll: ATap::calling('showAllItSaid', 'Asking'), showTheNewest: ATap::calling('showTheNewestItSaid', 'Asking'))->newest)->toBeNull();
});
