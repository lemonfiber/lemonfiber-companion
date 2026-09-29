<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ADownloadOnDisk;
use Modules\Kernel\Api\ARatio;
use Modules\Kernel\Api\RoomSaysNothing;
use Modules\Kernel\Api\WhatLettingItGoCosts;
use Modules\Kernel\Api\WhereADownloadStands;

/** A seeding download an offer is about. */
function aDownloadAnOfferIsAbout(): ADownloadOnDisk
{
    return ADownloadOnDisk::seeding('Show.Season1', 4_000, ARatio::inHundredths(80), 'Your ratio on it stops growing');
}

it('carries the download, what goes with it and the name the offer goes by, as the stack gave them', function (): void {
    $offer = WhatLettingItGoCosts::offered(aDownloadAnOfferIsAbout(), 'The copy in the downloads tree goes with it', 'stop-seeding-show-season1');

    expect([$offer->download()->name(), $offer->download()->stands(), $offer->download()->consequence()])
        ->toBe(['Show.Season1', WhereADownloadStands::Seeding, 'Your ratio on it stops growing'])
        ->and($offer->goes())->toBe('The copy in the downloads tree goes with it')
        ->and($offer->agreement())->toBe('stop-seeding-show-season1');
});

it('refuses an offer that will not say what goes, or cannot be named', function (): void {
    expect(fn(): WhatLettingItGoCosts => WhatLettingItGoCosts::offered(aDownloadAnOfferIsAbout(), ' ', 'stop-seeding-show-season1'))
        ->toThrow(RoomSaysNothing::class, '`goes`')
        ->and(fn(): WhatLettingItGoCosts => WhatLettingItGoCosts::offered(aDownloadAnOfferIsAbout(), 'It goes', ' '))
        ->toThrow(RoomSaysNothing::class, '`agreement`');
});
