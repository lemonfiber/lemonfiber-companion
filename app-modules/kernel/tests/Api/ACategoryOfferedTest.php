<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACategoryOffered;
use Modules\Kernel\Api\RoomSaysNothing;
use Modules\Kernel\Api\WhatALineIsAbout;
use Modules\Kernel\Api\WhatItOccupies;

it('carries what the category is, what it occupies and how many files it holds', function (): void {
    $occupies = WhatItOccupies::counted(31, 30);
    $offered = ACategoryOffered::holding(WhatALineIsAbout::Extracted, $occupies, 0);

    expect([$offered->about(), $offered->occupies(), $offered->files()])->toBe([WhatALineIsAbout::Extracted, $occupies, 0]);
});

it('refuses a file count below nothing', function (): void {
    expect(fn(): ACategoryOffered => ACategoryOffered::holding(WhatALineIsAbout::Orphaned, WhatItOccupies::counted(1, 1), -1))
        ->toThrow(RoomSaysNothing::class, '`files`');
});
