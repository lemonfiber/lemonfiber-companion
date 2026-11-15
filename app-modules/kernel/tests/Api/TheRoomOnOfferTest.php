<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function count;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ACategoryOffered;
use Modules\Kernel\Api\RoomSaysNothing;
use Modules\Kernel\Api\TheRoomOnOffer;
use Modules\Kernel\Api\WhatALineIsAbout;
use Modules\Kernel\Api\WhatItOccupies;

it('carries the name the offer goes by and each category in the order given', function (): void {
    $orphaned = ACategoryOffered::holding(WhatALineIsAbout::Orphaned, WhatItOccupies::counted(2, 2), 3);
    $extracted = ACategoryOffered::holding(WhatALineIsAbout::Extracted, WhatItOccupies::counted(5, 5), 1);
    $offer = TheRoomOnOffer::named('space-3f9a', ...['first' => $orphaned, 'second' => $extracted]);

    expect($offer->agreement())->toBe('space-3f9a')
        ->and(array_keys(iterator_to_array($offer, preserve_keys: true)))->toBe([0, 1])
        ->and(iterator_to_array($offer, preserve_keys: false))->toBe([$orphaned, $extracted])
        ->and(count($offer))->toBe(2);
});

it('holds a category about a word only where the offer names one', function (): void {
    $offer = TheRoomOnOffer::named('space-3f9a', ACategoryOffered::holding(WhatALineIsAbout::Orphaned, WhatItOccupies::counted(2, 2), 3), ACategoryOffered::holding(WhatALineIsAbout::Extracted, WhatItOccupies::counted(5, 5), 1));

    expect($offer->holds(WhatALineIsAbout::Extracted))->toBeTrue()
        ->and($offer->holds(WhatALineIsAbout::Seeding))->toBeFalse()
        ->and(TheRoomOnOffer::named('space-3f9a')->holds(WhatALineIsAbout::Orphaned))->toBeFalse();
});

it('refuses an offer with no name to quote', function (): void {
    expect(fn(): TheRoomOnOffer => TheRoomOnOffer::named(' '))->toThrow(RoomSaysNothing::class, '`agreement`');
});
