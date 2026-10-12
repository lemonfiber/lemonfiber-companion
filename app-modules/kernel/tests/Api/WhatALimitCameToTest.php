<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_filter;
use function array_values;
use function expect;
use function it;

use Modules\Kernel\Api\WhatALimitCameTo;

it('marks a limit ignored or overrun as a miss, and nothing else', function (): void {
    $misses = array_values(array_filter(WhatALimitCameTo::cases(), static fn(WhatALimitCameTo $verdict): bool => $verdict->isAMiss()));

    expect($misses)->toBe([WhatALimitCameTo::Ignored, WhatALimitCameTo::Overrunning]);
});

it('names a catalogue line for each', function (): void {
    expect(WhatALimitCameTo::NothingToLimit->saidOnTheScreen())->toBe('stacks.line_change.verdict.nothing-to-limit');
});
