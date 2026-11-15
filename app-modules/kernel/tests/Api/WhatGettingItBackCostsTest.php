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

it('costs nothing only where the line is one the stack offers back for nothing', function (WhatGettingItBackCosts $case, bool $free): void {
    expect($case->costsNothing())->toBe($free);
})->with([
    'nothing ever took it' => [WhatGettingItBackCosts::TheEasyWin, true],
    'the unpacked copy is in use' => [WhatGettingItBackCosts::AlreadyHaveIt, true],
    'by losing content' => [WhatGettingItBackCosts::ByLosingContent, false],
    'being written' => [WhatGettingItBackCosts::InProgress, false],
    'at the cost of ratio' => [WhatGettingItBackCosts::AtTheCostOfRatio, false],
    'rarely worth it' => [WhatGettingItBackCosts::Marginally, false],
    'left alone' => [WhatGettingItBackCosts::YouSaidNot, false],
]);
