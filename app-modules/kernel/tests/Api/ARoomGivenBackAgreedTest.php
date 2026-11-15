<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACategoryOffered;
use Modules\Kernel\Api\ARoomGivenBackAgreed;
use Modules\Kernel\Api\ARoomGivenBackWasNotOffered;
use Modules\Kernel\Api\TheRoomOnOffer;
use Modules\Kernel\Api\WhatALineIsAbout;
use Modules\Kernel\Api\WhatItOccupies;

it('quotes the offer by its name and carries the one category agreed to', function (): void {
    $offer = TheRoomOnOffer::named('space-3f9a', ACategoryOffered::holding(WhatALineIsAbout::Orphaned, WhatItOccupies::counted(2, 2), 3));
    $agreed = ARoomGivenBackAgreed::to($offer, WhatALineIsAbout::Orphaned);

    expect([$agreed->agreement(), $agreed->category()])->toBe(['space-3f9a', WhatALineIsAbout::Orphaned]);
});

it('refuses a category the offer does not hold, naming it', function (): void {
    $offer = TheRoomOnOffer::named('space-3f9a', ACategoryOffered::holding(WhatALineIsAbout::Orphaned, WhatItOccupies::counted(2, 2), 3));

    expect(fn(): ARoomGivenBackAgreed => ARoomGivenBackAgreed::to($offer, WhatALineIsAbout::Extracted))
        ->toThrow(ARoomGivenBackWasNotOffered::class, 'Giving back `extracted` was agreed to, and the offer read holds no such category.');
});
