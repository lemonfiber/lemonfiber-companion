<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhatGettingItBackCosts;

use function sprintf;

it('names a catalogue key for each case, from its wire word', function (WhatGettingItBackCosts $case): void {
    expect($case->saidOnTheScreen())->toBe(sprintf('stacks.room.reclaim.%s', $case->value));
})->with(WhatGettingItBackCosts::cases());
