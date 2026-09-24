<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhatALineIsAbout;

use function sprintf;

it('names a catalogue key for each case, from its wire word', function (WhatALineIsAbout $case): void {
    expect($case->saidOnTheScreen())->toBe(sprintf('stacks.room.about.%s', $case->value));
})->with(WhatALineIsAbout::cases());
