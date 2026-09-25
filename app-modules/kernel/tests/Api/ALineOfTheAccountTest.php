<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ALineOfTheAccount;
use Modules\Kernel\Api\RoomSaysNothing;
use Modules\Kernel\Api\WhatALineIsAbout;
use Modules\Kernel\Api\WhatGettingItBackCosts;
use Modules\Kernel\Api\WhatItOccupies;

it('N12-R6 — a tree carries its name, and nothing else carries one', function (): void {
    $occupies = WhatItOccupies::counted(2, 1);
    $tree = ALineOfTheAccount::forTheTree('movies', $occupies, WhatGettingItBackCosts::ByLosingContent);
    $landing = ALineOfTheAccount::for(WhatALineIsAbout::Landing, $occupies, WhatGettingItBackCosts::InProgress);

    expect([$tree->about(), $tree->tree(), $tree->occupies(), $tree->costs()])->toBe([WhatALineIsAbout::Tree, 'movies', $occupies, WhatGettingItBackCosts::ByLosingContent])
        ->and([$landing->about(), $landing->tree(), $landing->costs()])->toBe([WhatALineIsAbout::Landing, '', WhatGettingItBackCosts::InProgress]);
});

it('refuses a tree with no name, and a tree built as anything else', function (): void {
    $occupies = WhatItOccupies::counted(0, 0);

    expect(fn(): ALineOfTheAccount => ALineOfTheAccount::forTheTree(' ', $occupies, WhatGettingItBackCosts::Marginally))->toThrow(RoomSaysNothing::class, '`name`')
        ->and(fn(): ALineOfTheAccount => ALineOfTheAccount::for(WhatALineIsAbout::Tree, $occupies, WhatGettingItBackCosts::Marginally))->toThrow(RoomSaysNothing::class, '`name`');
});
